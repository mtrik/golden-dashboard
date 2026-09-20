<?php
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
// This file works directly with Golden Dashboard's own custom database tables
// (gd_user_wallet, gd_wallet_transactions, etc.), which have no WordPress core
// API equivalent, so direct $wpdb queries are required throughout. Every value
// that varies by request is passed through $wpdb->prepare() with %d/%s/%f
// placeholders (manually audited); object caching is intentionally not applied
// because wallet balances and transaction records must always reflect the
// latest write.



if (!defined('ABSPATH')) {
    exit;
}

class GDB_Wallet_Topup {

    public function __construct() {
        
        
        add_action('wp_ajax_gdb_process_topup', [$this, 'handle_topup_form_submission']);
        add_action('wp_ajax_nopriv_gdb_process_topup', [$this, 'handle_topup_form_submission']);

        
        add_action('woocommerce_before_calculate_totals', [$this, 'set_custom_topup_price'], 20, 1);

        
        
        
        
        add_action('woocommerce_order_status_changed', [$this, 'handle_order_status_change'], 10, 4);

        
        add_action('woocommerce_payment_complete', [$this, 'credit_wallet_on_order_complete'], 20, 1);

        
        add_action('woocommerce_order_refunded', [$this, 'handle_order_refunded'], 10, 2);

        
        
        
        add_action('woocommerce_order_note_added', [$this, 'save_admin_note_to_transaction'], 10, 2);

        
        add_filter('woocommerce_get_return_url', [$this, 'filter_topup_return_url'], 10, 2);

        
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_pay_page_styles'], 30);
    }



    public function handle_topup_form_submission() {
        
        
        if (!isset($_POST['gdb_topup_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gdb_topup_nonce'])), 'gdb_wallet_topup_action')) {
            wp_send_json_error(['message' => __('توکن امنیتی نامعتبر یا منقضی شده است. لطفاً صفحه را رفرش کرده و مجدداً تلاش کنید.', 'golden-dashboard')]);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('برای شارژ کیف پول ابتدا باید وارد حساب کاربری خود شوید.', 'golden-dashboard')]);
        }

        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        $amount_display = isset($_POST['topup_amount']) ? absint(wp_unslash($_POST['topup_amount'])) : 0;

        if ($amount_display <= 0) {
            wp_send_json_error(['message' => __('مبلغ وارد شده برای شارژ معتبر نیست.', 'golden-dashboard')]);
        }

        
        $amount_toman = gdb_storage_amount($amount_display);

        if ($amount_toman <= 0) {
            wp_send_json_error(['message' => __('مبلغ وارد شده برای شارژ معتبر نیست.', 'golden-dashboard')]);
        }

        $user_id = get_current_user_id();

        
        if (GDB_Security::is_ip_blocked(GDB_Security::get_client_ip())) {
            GDB_Security::log_event($user_id, 'blocked_ip_attempt', 'تلاش برای شارژ کیف پول از یک IP مسدود شده.', 'high', ['action' => 'wallet_topup']);
            wp_send_json_error(['message' => __('امکان انجام این عملیات از این آدرس وجود ندارد.', 'golden-dashboard')]);
        }

        $rate_limit_check = GDB_Security::check_rate_limit('user_' . $user_id, 'wallet_topup');
        if (is_wp_error($rate_limit_check)) {
            wp_send_json_error(['message' => $rate_limit_check->get_error_message()]);
        }

        $daily_limit_check = GDB_Security::check_daily_transaction_limit($user_id);
        if (is_wp_error($daily_limit_check)) {
            wp_send_json_error(['message' => $daily_limit_check->get_error_message()]);
        }

        $max_amount_check = GDB_Security::check_max_transaction_amount($amount_toman);
        if (is_wp_error($max_amount_check)) {
            wp_send_json_error(['message' => $max_amount_check->get_error_message()]);
        }
        

        if (!function_exists('wc_create_order')) {
            wp_send_json_error(['message' => __('ووکامرس در حال حاضر در دسترس نیست.', 'golden-dashboard')]);
        }

        
        $product_id = $this->get_or_create_topup_product();

        if (!$product_id) {
            wp_send_json_error(['message' => __('سیستم قادر به ایجاد محصول مجازی برای عملیات شارژ نیست.', 'golden-dashboard')]);
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(['message' => __('محصول شارژ کیف پول یافت نشد.', 'golden-dashboard')]);
        }

        
        $return_url = $this->get_safe_return_url();

        try {
            $order = wc_create_order([
                'customer_id' => $user_id,
                'created_via' => 'golden-dashboard-wallet-topup',
            ]);

            if (is_wp_error($order)) {
                throw new Exception($order->get_error_message());
            }

            
            
            
            $item_id = $order->add_product($product, 1, [
                'subtotal' => $amount_display,
                'total'    => $amount_display,
            ]);

            if (!$item_id) {
                throw new Exception('add_product failed');
            }

            
            $this->apply_customer_address_from_profile($order, $user_id);

            $order->set_currency(get_woocommerce_currency());
            $order->calculate_totals();

            $order->update_meta_data('_gdb_is_topup', 'yes');
            
            
            
            $order->update_meta_data('_gdb_topup_amount', $amount_toman);
            $order->update_meta_data('_gdb_return_url', $return_url);

            $order->set_status('pending', __('سفارش شارژ کیف پول ایجاد شد و در انتظار پرداخت است.', 'golden-dashboard'));
            $order->save();

            
            
            
            $this->create_initial_transaction($user_id, $amount_toman, $order->get_id());

        } catch (Exception $e) {
            wc_get_logger()->error(
                sprintf('ایجاد سفارش شارژ کیف پول ناموفق بود: %s', $e->getMessage()),
                ['source' => 'gdb-wallet-topup']
            );
            wp_send_json_error(['message' => __('در ایجاد سفارش شارژ کیف پول مشکلی پیش آمد. لطفاً مجدداً تلاش کنید.', 'golden-dashboard')]);
        }

        
        
        wp_send_json_success(['redirect_url' => $order->get_checkout_payment_url()]);
    }



    private function create_initial_transaction($user_id, $amount, $order_id) {
        global $wpdb;

        $balance = GDB_Wallet::balance($user_id);
        $table = $wpdb->prefix . 'gd_wallet_transactions';

        
        $tracking_code = function_exists('gdb_generate_tracking_code') 
            ? gdb_generate_tracking_code() 
            : (string) wp_rand(100000000000, 999999999999);

        
        $description = sprintf(
            /* translators: 1: order ID, 2: tracking code */
            __('سفارش شارژ کیف پول شماره %1$d - کد پیگیری: %2$s (در انتظار پرداخت)', 'golden-dashboard'),
            $order_id,
            $tracking_code
        );

        $wpdb->insert(
            $table,
            [
                'user_id'          => $user_id,
                'type'             => 'credit',
                'amount'           => $amount,
                'balance_before'   => $balance,
                'balance_after'    => $balance,
                'transaction_type' => 'order_payment',
                'reference_id'     => $order_id,
                'description'      => $description,
                'status'           => 'pending',
                'tracking_code'    => $tracking_code,
                'admin_note'       => '',
                'bank_transaction_id' => '',
                'bank_date'        => '',
                'meta_data'        => maybe_serialize(['order_id' => $order_id, 'is_topup' => true]),
                'created_by'       => $user_id,
                'ip_address'       => sanitize_text_field((isset($_SERVER['REMOTE_ADDR']) ? wp_unslash($_SERVER['REMOTE_ADDR']) : '')),
                'user_agent'       => substr(sanitize_text_field((isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '')), 0, 255),
                'session_id'       => function_exists('session_id') && session_status() === PHP_SESSION_ACTIVE ? session_id() : '',
                'is_suspicious'    => 0,
                'created_at'       => current_time('mysql'),
                'updated_at'       => current_time('mysql'),
            ],
            [
                '%d', '%s', '%f', '%f', '%f', '%s', '%d', '%s',
                '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s',
                '%s', '%s', '%d', '%s', '%s'
            ]
        );

        
        $order = wc_get_order($order_id);
        if ($order) {
            $order->update_meta_data('_gdb_topup_tracking_code', $tracking_code);
            $order->save();
        }
    }



    public function handle_order_status_change($order_id, $old_status, $new_status, $order) {
        if (!$order instanceof WC_Order) {
            return;
        }

        if ($order->get_meta('_gdb_is_topup') !== 'yes') {
            return;
        }

        $funds_credited = $order->get_meta('_gdb_wallet_funds_credited') === 'yes';

        
        if ($new_status === 'completed') {
            if (!$funds_credited) {
                $this->credit_wallet_on_order_complete($order_id);
            }
            return;
        }

        
        
        
        
        
        
        
        
        
        
        
        
        
        if ($old_status === 'completed' && $funds_credited) {
            $this->log_topup_status_change_without_reversal($order_id, $new_status);
            return;
        }

        
        $this->update_transaction_status($order_id, $new_status);
    }



    public function handle_order_refunded($order_id, $refund_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        if ($order->get_meta('_gdb_is_topup') !== 'yes') {
            return;
        }

        
        if ($order->get_meta('_gdb_wallet_funds_credited') === 'yes') {
            $this->reverse_wallet_credit($order_id);
        }
    }



    private function log_topup_status_change_without_reversal($order_id, $new_status) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        $user_id = $order->get_user_id();
        if (!$user_id) {
            return;
        }
        if ($order->get_meta('_gdb_topup_status_logged_' . $new_status) === 'yes') {
            return;
        }

        global $wpdb;
        $table_trans = $wpdb->prefix . 'gd_wallet_transactions';
        $current_balance = GDB_Wallet::balance($user_id);
        $tracking_code = function_exists('gdb_generate_tracking_code') ? gdb_generate_tracking_code() : wp_rand(100000000000, 999999999999);

        $status_action_labels = [
            'cancelled' => __('لغو شد', 'golden-dashboard'),
            'failed'    => __('ناموفق بود', 'golden-dashboard'),
            'refunded'  => __('بازگشت داده شد', 'golden-dashboard'),
            'on-hold'   => __('در انتظار بررسی قرار گرفت', 'golden-dashboard'),
            'pending'   => __('به حالت در انتظار برگشت', 'golden-dashboard'),
        ];
        $status_label = isset($status_action_labels[$new_status]) ? $status_action_labels[$new_status] : $new_status;

        $wpdb->insert($table_trans, [
            'user_id'          => $user_id,
            'type'             => 'credit',
            'amount'           => 0,
            'balance_before'   => $current_balance,
            'balance_after'    => $current_balance,
            'transaction_type' => 'order_payment',
            'reference_id'     => $order_id,
            'description'      => sprintf(
                /* translators: 1: order ID, 2: new status label, 3: tracking code */
                __('وضعیتِ سفارشِ شارژِ کیف پول شماره %1$d به «%2$s» تغییر کرد؛ چون این صرفاً تغییرِ وضعیت است (نه استردادِ رسمی از طریق ووکامرس)، موجودیِ کیف پول دست‌نخورده باقی ماند - کد پیگیری: %3$s', 'golden-dashboard'),
                $order_id,
                $status_label,
                $tracking_code
            ),
            'created_by'       => 0,
            'ip_address'       => GDB_Security::get_client_ip(),
            'status'           => 'completed',
            'tracking_code'    => $tracking_code,
            'created_at'       => current_time('mysql'),
            'updated_at'       => current_time('mysql'),
        ]);

        $order->update_meta_data('_gdb_topup_status_logged_' . $new_status, 'yes');
        $order->save();
    }



    private function reverse_wallet_credit($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        
        if ($order->get_meta('_gdb_wallet_funds_credited') !== 'yes') {
            return;
        }

        $user_id = $order->get_user_id();
        if (!$user_id) {
            return;
        }

        
        
        
        
        $topup_amount = (float) $order->get_meta('_gdb_topup_amount');

        if ($topup_amount <= 0) {
            
            
            $saved_topup_product_id = get_option('gdb_wallet_topup_product_id', 0);
            foreach ($order->get_items() as $item) {
                if ($item->get_product_id() == $saved_topup_product_id) {
                    $topup_amount = gdb_storage_amount(wc_format_decimal($item->get_total(), wc_get_price_decimals()));
                    break;
                }
            }
        }

        if ($topup_amount <= 0) {
            return;
        }

        global $wpdb;

        $wpdb->query('START TRANSACTION');

        try {
            $table_wallet       = $wpdb->prefix . 'gd_user_wallet';
            $table_transactions = $wpdb->prefix . 'gd_wallet_transactions';
            $table_security     = $wpdb->prefix . 'gd_wallet_security_log';

            
            $wallet = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$table_wallet} WHERE user_id = %d FOR UPDATE",
                    $user_id
                )
            );

            if (!$wallet) {
                throw new Exception(__('کیف پول کاربر یافت نشد.', 'golden-dashboard'));
            }

            $balance_before = (float) $wallet->balance;
            $balance_after = max(0, $balance_before - $topup_amount); 

            
            
            
            
            
            
            
            
            $wpdb->update(
                $table_wallet,
                [
                    'balance'      => $balance_after,
                    'total_credit' => max(0, (float) $wallet->total_credit - $topup_amount),
                    'updated_at'   => current_time('mysql')
                ],
                ['user_id' => $user_id],
                ['%f', '%f', '%s'],
                ['%d']
            );

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            
            
            
            
            
            
            
            
            $tracking_code = function_exists('gdb_generate_tracking_code') ? gdb_generate_tracking_code() : wp_rand(100000000000, 999999999999);
            $wpdb->insert(
                $table_transactions,
                [
                    'user_id'         => $user_id,
                    'type'            => 'debit',
                    'amount'          => $topup_amount,
                    'balance_before'  => $balance_before,
                    'balance_after'   => $balance_after,
                    'transaction_type' => 'order_refund',
                    'reference_id'    => $order_id,
                    'description'     => sprintf(
                        /* translators: 1: order ID, 2: tracking code */
                        __('برداشت کیف پول به دلیل لغو/استرداد سفارش شماره %1$d - کد پیگیری: %2$s', 'golden-dashboard'),
                        $order_id,
                        $tracking_code
                    ),
                    'status'          => 'completed',
                    'tracking_code'   => $tracking_code,
                    'created_by'      => get_current_user_id() ?: 1,
                    'ip_address'      => sanitize_text_field((isset($_SERVER['REMOTE_ADDR']) ? wp_unslash($_SERVER['REMOTE_ADDR']) : '')),
                    'user_agent'      => substr(sanitize_text_field((isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '')), 0, 255),
                    'session_id'      => function_exists('session_id') && session_status() === PHP_SESSION_ACTIVE ? session_id() : '',
                    'is_suspicious'   => 0,
                    'created_at'      => current_time('mysql'),
                    'updated_at'      => current_time('mysql'),
                ],
                [
                    '%d', '%s', '%f', '%f', '%f', '%s', '%d', '%s',
                    '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s'
                ]
            );

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            
            $wpdb->insert(
                $table_security,
                [
                    'user_id'      => $user_id,
                    'event_type'   => 'wallet_debited',
                    'severity'     => 'low',
                    'message'      => 'برداشت کیف پول به دلیل لغو/استرداد سفارش ' . $order_id,
                    'ip_address'   => sanitize_text_field((isset($_SERVER['REMOTE_ADDR']) ? wp_unslash($_SERVER['REMOTE_ADDR']) : '')),
                    'user_agent'   => substr(sanitize_text_field((isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '')), 0, 255),
                    'request_data' => wp_json_encode([
                        'order_id' => $order_id,
                        'amount'   => $topup_amount
                    ]),
                    'created_at'   => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            
            $order->update_meta_data('_gdb_wallet_funds_credited', 'no');
            $order->save();

            $order->add_order_note(
                sprintf(
                    'مبلغ %s از کیف پول کاربر برداشت شد (به دلیل لغو/استرداد سفارش).',
                    gdb_price($topup_amount)
                )
            );

            $wpdb->query('COMMIT');

            
            if (class_exists('GDB_Cashback')) {
                GDB_Cashback::reverse_for_order($order_id);
            }

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');

            $order->add_order_note(
                'خطا در برداشت از کیف پول: ' . $e->getMessage()
            );

            wc_get_logger()->error(
                sprintf('برداشت از کیف پول برای سفارش شماره %d ناموفق بود: %s', $order_id, $e->getMessage()),
                ['source' => 'gdb-wallet-topup']
            );
        }
    }



    private function update_transaction_status($order_id, $status) {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_transactions';

        
        $tracking_code = '';
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT tracking_code FROM {$table} WHERE reference_id = %d AND transaction_type = 'order_payment'",
            $order_id
        ));
        if ($existing && !empty($existing->tracking_code)) {
            $tracking_code = $existing->tracking_code;
        }

        $texts = [
            'pending'    => 'در انتظار پرداخت',
            'processing' => 'پرداخت انجام شده و سفارش در حال بررسی است.',
            'cancelled'  => 'سفارش لغو شد.',
            'failed'     => 'پرداخت ناموفق بود.',
            'on-hold'    => 'در انتظار بررسی',
            
        ];

        $description = isset($texts[$status]) 
            ? sprintf('%s - کد پیگیری: %s', $texts[$status], $tracking_code)
            : sprintf('%s - کد پیگیری: %s', $status, $tracking_code);

        $wpdb->update(
            $table,
            [
                'status'      => $status,
                'description' => $description,
                'updated_at'  => current_time('mysql')
            ],
            [
                'reference_id' => $order_id,
                'transaction_type' => 'order_payment'
            ],
            ['%s', '%s', '%s'],
            ['%d', '%s']
        );
    }



    public function credit_wallet_on_order_complete($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        
        if ($order->get_meta('_gdb_wallet_funds_credited') === 'yes') {
            return;
        }

        
        if ($order->get_meta('_gdb_is_topup') !== 'yes') {
            return;
        }

        $user_id = $order->get_user_id();
        if (!$user_id) {
            return;
        }

        
        
        
        
        $topup_amount = (float) $order->get_meta('_gdb_topup_amount');

        if ($topup_amount <= 0) {
            
            
            $saved_topup_product_id = get_option('gdb_wallet_topup_product_id', 0);
            foreach ($order->get_items() as $item) {
                if ($item->get_product_id() == $saved_topup_product_id) {
                    $topup_amount = gdb_storage_amount(wc_format_decimal($item->get_total(), wc_get_price_decimals()));
                    break;
                }
            }
        }

        if ($topup_amount <= 0) {
            return;
        }

        global $wpdb;

        $wpdb->query('START TRANSACTION');

        try {
            $table_wallet       = $wpdb->prefix . 'gd_user_wallet';
            $table_transactions = $wpdb->prefix . 'gd_wallet_transactions';
            $table_security     = $wpdb->prefix . 'gd_wallet_security_log';

            
            $wallet = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$table_wallet} WHERE user_id = %d FOR UPDATE",
                    $user_id
                )
            );

            if ($wallet) {
                $balance_before = (float) $wallet->balance;
                $balance_after = $balance_before + $topup_amount;

                $wpdb->update(
                    $table_wallet,
                    [
                        'balance'      => $balance_after,
                        'total_credit' => (float) $wallet->total_credit + $topup_amount,
                        'updated_at'   => current_time('mysql')
                    ],
                    ['user_id' => $user_id],
                    ['%f', '%f', '%s'],
                    ['%d']
                );
            } else {
                $balance_before = 0;
                $balance_after = $topup_amount;

                $wpdb->insert(
                    $table_wallet,
                    [
                        'user_id'      => $user_id,
                        'balance'      => $balance_after,
                        'total_credit' => $topup_amount,
                        'total_debit'  => 0,
                        'created_at'   => current_time('mysql'),
                        'updated_at'   => current_time('mysql')
                    ],
                    ['%d', '%f', '%f', '%f', '%s', '%s']
                );
            }

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            
            $tracking_code = '';

            
            $tracking_code = $order->get_meta('_gdb_topup_tracking_code');

            
            if (empty($tracking_code)) {
                $existing_tx_check = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT tracking_code FROM {$table_transactions} WHERE reference_id = %d AND transaction_type = 'order_payment'",
                        $order_id
                    )
                );
                if ($existing_tx_check && !empty($existing_tx_check->tracking_code)) {
                    $tracking_code = $existing_tx_check->tracking_code;
                }
            }

            
            if (empty($tracking_code)) {
                $tracking_code = function_exists('gdb_generate_tracking_code') 
                    ? gdb_generate_tracking_code() 
                    : (string) wp_rand(100000000000, 999999999999);
            }

            
            $existing_tx = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id FROM {$table_transactions} WHERE reference_id = %d AND transaction_type = 'order_payment'",
                    $order_id
                )
            );

            $description = sprintf(
                /* translators: 1: order ID, 2: tracking code */
                __('شارژ کیف پول از سفارش شماره %1$d - کد پیگیری: %2$s (تایید شده)', 'golden-dashboard'),
                $order_id,
                $tracking_code
            );

            if ($existing_tx) {
                $wpdb->update(
                    $table_transactions,
                    [
                        'balance_before' => $balance_before,
                        'balance_after'  => $balance_after,
                        'status'         => 'completed',
                        'tracking_code'  => $tracking_code,
                        'description'    => $description,
                        'updated_at'     => current_time('mysql'),
                    ],
                    ['id' => $existing_tx->id],
                    ['%f', '%f', '%s', '%s', '%s', '%s'],
                    ['%d']
                );
                $tx_id = $existing_tx->id;
            } else {
                
                $wpdb->insert(
                    $table_transactions,
                    [
                        'user_id'         => $user_id,
                        'type'            => 'credit',
                        'amount'          => $topup_amount,
                        'balance_before'  => $balance_before,
                        'balance_after'   => $balance_after,
                        'transaction_type' => 'admin_credit',
                        'reference_id'    => $order_id,
                        'description'     => $description,
                        'status'          => 'completed',
                        'tracking_code'   => $tracking_code,
                        'created_by'      => get_current_user_id() ?: 1,
                        'ip_address'      => sanitize_text_field((isset($_SERVER['REMOTE_ADDR']) ? wp_unslash($_SERVER['REMOTE_ADDR']) : '')),
                        'user_agent'      => substr(sanitize_text_field((isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '')), 0, 255),
                        'session_id'      => function_exists('session_id') && session_status() === PHP_SESSION_ACTIVE ? session_id() : '',
                        'is_suspicious'   => 0,
                        'created_at'      => current_time('mysql'),
                        'updated_at'      => current_time('mysql'),
                    ],
                    [
                        '%d', '%s', '%f', '%f', '%f', '%s', '%d', '%s',
                        '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s'
                    ]
                );
                $tx_id = $wpdb->insert_id;
            }

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            
            
            GDB_Security::log_event(
                $user_id,
                'wallet_credited',
                'شارژ کیف پول از سفارش ' . $order_id . ' - کد پیگیری: ' . $tracking_code,
                'low',
                ['order_id' => $order_id, 'amount' => $topup_amount, 'tracking_code' => $tracking_code]
            );

            
            if (!empty($tx_id)) {
                GDB_Security::flag_transaction_if_suspicious($tx_id, $topup_amount, $user_id, 'شارژ کیف پول');

                if (class_exists('GDB_Cashback')) {
                    GDB_Cashback::maybe_apply($user_id, $topup_amount, 'topup', $order_id);
                }
            }

            
            $order->update_meta_data('_gdb_wallet_funds_credited', 'yes');
            $order->update_meta_data('_gdb_topup_tracking_code', $tracking_code);
            $order->save();

            $order->add_order_note(
                sprintf(
                    'کیف پول کاربر به مبلغ %s شارژ شد. کد پیگیری: %s',
                    gdb_price($topup_amount),
                    $tracking_code
                )
            );

            $wpdb->query('COMMIT');

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');

            $order->add_order_note(
                'خطا در شارژ کیف پول: ' . $e->getMessage()
            );

            wc_get_logger()->error(
                sprintf('شارژ کیف پول برای سفارش شماره %d ناموفق بود: %s', $order_id, $e->getMessage()),
                ['source' => 'gdb-wallet-topup']
            );
        }
    }



    public function save_admin_note_to_transaction($note_id, $order) {
        global $wpdb;

        if (!$order instanceof WC_Order) {
            return;
        }

        if ($order->get_meta('_gdb_is_topup') !== 'yes') {
            return;
        }

        $note = wc_get_order_note($note_id);
        if (!$note) {
            return;
        }

        
        if ($note->added_by === 'system') {
            return;
        }

        $wpdb->update(
            $wpdb->prefix . 'gd_wallet_transactions',
            [
                'admin_note' => sanitize_textarea_field($note->content),
                'updated_at' => current_time('mysql')
            ],
            [
                'reference_id' => $order->get_id(),
                'transaction_type' => 'order_payment'
            ],
            ['%s', '%s'],
            ['%d', '%s']
        );
    }



    private function get_safe_return_url() {
        return gdb_get_safe_return_url('gdb_return_url');
    }



    private function apply_customer_address_from_profile($order, $user_id) {
        $user = get_userdata($user_id);

        $billing_fields = [
            'first_name', 'last_name', 'company', 'address_1', 'address_2',
            'city', 'state', 'postcode', 'country', 'email', 'phone',
        ];

        $billing = [];
        foreach ($billing_fields as $field) {
            $billing[$field] = get_user_meta($user_id, 'billing_' . $field, true);
        }

        if (empty($billing['email']) && $user) {
            $billing['email'] = $user->user_email;
        }

        if ($user) {
            if (empty($billing['first_name'])) {
                $billing['first_name'] = $user->first_name;
            }
            if (empty($billing['last_name'])) {
                $billing['last_name'] = $user->last_name;
            }
        }

        $order->set_address($billing, 'billing');

        $shipping_fields = [
            'first_name', 'last_name', 'company', 'address_1', 'address_2',
            'city', 'state', 'postcode', 'country',
        ];

        $shipping     = [];
        $has_shipping = false;

        foreach ($shipping_fields as $field) {
            $value = get_user_meta($user_id, 'shipping_' . $field, true);
            if ($value !== '') {
                $has_shipping = true;
            }
            $shipping[$field] = $value;
        }

        $order->set_address($has_shipping ? $shipping : $billing, 'shipping');
    }



    public function filter_topup_return_url($return_url, $order) {
        if (!$order instanceof WC_Order) {
            return $return_url;
        }

        if ($order->get_meta('_gdb_is_topup') !== 'yes') {
            return $return_url;
        }

        $target = $order->get_meta('_gdb_return_url');

        if (!$target) {
            return $return_url;
        }

        return add_query_arg(
            [
                'gdb_topup_result' => 1,
                'order_id'         => $order->get_id(),
                'key'              => $order->get_order_key(),
            ],
            $target
        );
    }



    public function maybe_enqueue_pay_page_styles() {
        if (!function_exists('is_checkout_pay_page') || !is_checkout_pay_page()) {
            return;
        }

        global $wp;

        $order_id = isset($wp->query_vars['order-pay']) ? absint($wp->query_vars['order-pay']) : 0;

        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);

        if (!$order || $order->get_meta('_gdb_is_topup') !== 'yes') {
            return;
        }

        $css = '
            .woocommerce-order-pay table.shop_table,
            .woocommerce-order-pay #order_review_heading {
                display: none !important;
            }
            .woocommerce-order-pay #payment {
                border-radius: 12px;
                overflow: hidden;
            }
        ';

        wp_register_style('gdb-topup-pay-page', false, [], GDB_VERSION);
        wp_enqueue_style('gdb-topup-pay-page');
        wp_add_inline_style('gdb-topup-pay-page', $css);
    }



    private function get_or_create_topup_product() {
        $product_id = get_option('gdb_wallet_topup_product_id', 0);

        if ($product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                return $product_id;
            }
        }

        $product = new WC_Product_Simple();
        $product->set_name(__('شارژ کیف پول', 'golden-dashboard'));
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_price(0);
        $product->set_regular_price(0);
        $product->set_virtual(true);
        $product->set_sold_individually(true);
        $product_id = $product->save();

        update_option('gdb_wallet_topup_product_id', $product_id);

        return $product_id;
    }



    public function set_custom_topup_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['gdb_is_topup']) && isset($cart_item['gdb_topup_amount'])) {
                $price = floatval($cart_item['gdb_topup_amount']);
                $cart_item['data']->set_price($price);
            }
        }
    }
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange

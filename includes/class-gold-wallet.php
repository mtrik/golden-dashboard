<?php

if (!defined('ABSPATH')) {
    exit;
}

class GDB_Gold_Wallet {

    public function __construct() {
        add_action('wp_ajax_gdb_gold_wallet_get_price', [$this, 'ajax_get_price']);
        add_action('wp_ajax_nopriv_gdb_gold_wallet_get_price', [$this, 'ajax_get_price']);

        add_action('wp_ajax_gdb_process_gold_purchase', [$this, 'ajax_process_purchase']);
        add_action('wp_ajax_nopriv_gdb_process_gold_purchase', [$this, 'ajax_process_purchase']);

        add_action('wp_ajax_gdb_get_gold_balances_html', [$this, 'ajax_get_balances_html']);


        add_action('woocommerce_payment_complete', [$this, 'credit_gold_on_order_complete'], 25, 1);
        add_action('woocommerce_order_status_changed', [$this, 'handle_gold_order_status_change'], 10, 4);
        add_action('woocommerce_order_status_changed', [$this, 'handle_gold_order_cancelled'], 10, 4);



        add_filter('woocommerce_get_return_url', [$this, 'filter_gold_return_url'], 999, 2);
        add_action('wp_ajax_gdb_get_gold_order_summary', [$this, 'ajax_get_gold_order_summary']);
        add_action('wp_ajax_nopriv_gdb_get_gold_order_summary', [$this, 'ajax_get_gold_order_summary']);





        add_action('wp_head', [$this, 'hide_order_review_table_for_gold_purchase']);
    }



    public function hide_order_review_table_for_gold_purchase() {
        if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-pay')) {
            return;
        }
        $order_id = absint(get_query_var('order-pay'));
        if (!$order_id) {
            return;
        }
        $order = wc_get_order($order_id);
        if (!$order || $order->get_meta('_gdb_is_gold_purchase') !== 'yes') {
            return;
        }
        echo '<style>table.shop_table{display:none !important;}</style>';
    }



    public static function get_types($active_only = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_gold_wallet_types';
        $sql = "SELECT * FROM {$table}";
        if ($active_only) {
            $sql .= " WHERE status = 'active'";
        }
        $sql .= " ORDER BY id DESC";
        return $wpdb->get_results($sql);
    }

    public static function get_type($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_gold_wallet_types';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
    }

    public static function save_type($data, $id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_gold_wallet_types';

        $fields = [
            'name'              => sanitize_text_field($data['name'] ?? ''),
            'unit_label'        => sanitize_text_field($data['unit_label'] ?? 'گرم'),
            'wc_product_id'     => absint($data['wc_product_id'] ?? 0),
            'suggested_amounts' => sanitize_text_field($data['suggested_amounts'] ?? ''),
            'min_amount'        => floatval($data['min_amount'] ?? 0),
            'max_amount'        => floatval($data['max_amount'] ?? 0),
            'step_amount'       => floatval($data['step_amount'] ?? 0.1),
            'payment_mode'      => in_array($data['payment_mode'] ?? '', ['wallet_only', 'wallet_or_gateway']) ? $data['payment_mode'] : 'wallet_only',
            'status'            => ($data['status'] ?? 'active') === 'active' ? 'active' : 'inactive',
            'updated_at'        => current_time('mysql'),
        ];

        if ($id > 0) {
            $wpdb->update($table, $fields, ['id' => $id]);
            return $id;
        }

        $fields['created_at'] = current_time('mysql');
        $wpdb->insert($table, $fields);
        return $wpdb->insert_id;
    }

    public static function delete_type($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_gold_wallet_types';
        return $wpdb->delete($table, ['id' => absint($id)], ['%d']);
    }



    public static function get_live_price($type) {
        if (!function_exists('wc_get_product')) {
            return 0;
        }
        $product = wc_get_product($type->wc_product_id);
        if (!$product) {
            return 0;
        }
        return (float) $product->get_price();
    }

    public static function balance($user_id, $type_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_user_metal_wallet';
        $balance = $wpdb->get_var($wpdb->prepare(
            "SELECT balance FROM {$table} WHERE user_id = %d AND gold_type_id = %d",
            $user_id, $type_id
        ));
        return $balance !== null ? (float) $balance : 0.0;
    }

    public static function get_user_balances($user_id) {
        global $wpdb;
        $table_balance = $wpdb->prefix . 'gd_user_metal_wallet';
        $table_types = $wpdb->prefix . 'gd_gold_wallet_types';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, t.name, t.unit_label, t.wc_product_id
             FROM {$table_balance} b
             INNER JOIN {$table_types} t ON t.id = b.gold_type_id
             WHERE b.user_id = %d AND b.balance > 0
             ORDER BY t.name ASC",
            $user_id
        ));
    }



    public static function render_balances_html($user_id, $empty_text = '') {
        $balances = self::get_user_balances($user_id);

        ob_start();
        if (empty($balances)) {
            echo '<p class="gdb-gold-balance-empty">' . esc_html($empty_text ?: __('شما هنوز طلایی خریداری نکرده‌اید.', 'golden-dashboard')) . '</p>';
        } else {
            echo '<div class="gdb-gold-balance-list">';
            foreach ($balances as $row) {
                $product = function_exists('wc_get_product') ? wc_get_product($row->wc_product_id) : null;
                $price = $product ? (float) $product->get_price() : 0;
                $equivalent = $price * (float) $row->balance;
                ?>
                <div class="gdb-gold-balance-item">
                    <div class="gdb-gold-balance-item-name"><?php echo esc_html($row->name); ?></div>
                    <div class="gdb-gold-balance-item-amount">
                        <?php echo esc_html(rtrim(rtrim(number_format((float) $row->balance, 3), '0'), '.')); ?> <?php echo esc_html($row->unit_label); ?>
                    </div>
                    <div class="gdb-gold-balance-item-equivalent">
                        <?php echo esc_html__('معادل ریالی لحظه‌ای:', 'golden-dashboard'); ?> <?php echo wp_kses_post(wc_price($equivalent)); ?>
                    </div>
                </div>
                <?php
            }
            echo '</div>';
        }
        return ob_get_clean();
    }

    public function ajax_get_balances_html() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('لطفاً وارد شوید.', 'golden-dashboard')]);
        }
        $user_id = get_current_user_id();
        $empty_text = isset($_POST['empty_text']) ? sanitize_text_field(wp_unslash($_POST['empty_text'])) : '';
        wp_send_json_success(['html' => self::render_balances_html($user_id, $empty_text)]);
    }

    private static function ensure_user_row($user_id, $type_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_user_metal_wallet';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND gold_type_id = %d",
            $user_id, $type_id
        ));
        if (!$exists) {
            $wpdb->insert($table, [
                'user_id'      => $user_id,
                'gold_type_id' => $type_id,
                'balance'      => 0,
                'total_credit' => 0,
                'total_debit'  => 0,
                'created_at'   => current_time('mysql'),
                'updated_at'   => current_time('mysql'),
            ]);
        }
    }



    private function purchase_from_wallet($user_id, $type, $quantity, $price, $rial_amount) {
        global $wpdb;

        self::ensure_user_row($user_id, $type->id);

        $wpdb->query('START TRANSACTION');

        try {
            $cash_balance = GDB_Wallet::balance($user_id);
            if ($rial_amount > $cash_balance) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('insufficient_balance', __('موجودی کیف پول شما برای این خرید کافی نیست.', 'golden-dashboard'));
            }

            $tracking_code = function_exists('gdb_generate_tracking_code') ? gdb_generate_tracking_code() : wp_generate_password(12, false);


            $cash_balance_after = $cash_balance - $rial_amount;
            $table_wallet = $wpdb->prefix . 'gd_user_wallet';
            $table_trans = $wpdb->prefix . 'gd_wallet_transactions';

            $wpdb->update($table_wallet, [
                'balance'     => $cash_balance_after,
                'total_debit' => $wpdb->get_var($wpdb->prepare("SELECT total_debit FROM {$table_wallet} WHERE user_id=%d", $user_id)) + $rial_amount,
                'updated_at'  => current_time('mysql'),
            ], ['user_id' => $user_id], ['%f', '%f', '%s'], ['%d']);

            $description = sprintf(
                __('خرید %s %s طلا (%s) از موجودی کیف پول - کد پیگیری: %s', 'golden-dashboard'),
                rtrim(rtrim(number_format($quantity, 3), '0'), '.'),
                $type->unit_label,
                $type->name,
                $tracking_code
            );

            $wpdb->insert($table_trans, [
                'user_id'         => $user_id,
                'type'            => 'debit',
                'amount'          => $rial_amount,
                'balance_before'  => $cash_balance,
                'balance_after'   => $cash_balance_after,
                'transaction_type' => 'gold_purchase',
                'reference_id'    => $type->id,
                'description'     => $description,
                'created_by'      => $user_id,
                'ip_address'      => GDB_Security::get_client_ip(),
                'user_agent'      => substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                'status'          => 'completed',
                'tracking_code'   => $tracking_code,
                'meta_data'       => maybe_serialize(['gold_type_id' => $type->id, 'quantity' => $quantity, 'price_per_unit' => $price]),
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql'),
            ]);
            $cash_tx_id = $wpdb->insert_id;


            $gold_balance_before = self::balance($user_id, $type->id);
            $gold_balance_after = $gold_balance_before + $quantity;

            $table_gold_wallet = $wpdb->prefix . 'gd_user_metal_wallet';
            $wpdb->update($table_gold_wallet, [
                'balance'      => $gold_balance_after,
                'total_credit' => $wpdb->get_var($wpdb->prepare("SELECT total_credit FROM {$table_gold_wallet} WHERE user_id=%d AND gold_type_id=%d", $user_id, $type->id)) + $quantity,
                'updated_at'   => current_time('mysql'),
            ], ['user_id' => $user_id, 'gold_type_id' => $type->id], ['%f', '%f', '%s'], ['%d', '%d']);

            $table_metal_trans = $wpdb->prefix . 'gd_metal_transactions';
            $wpdb->insert($table_metal_trans, [
                'user_id'         => $user_id,
                'gold_type_id'    => $type->id,
                'type'            => 'credit',
                'quantity'        => $quantity,
                'price_per_unit'  => $price,
                'rial_amount'     => $rial_amount,
                'balance_before'  => $gold_balance_before,
                'balance_after'   => $gold_balance_after,
                'payment_mode'    => 'wallet',
                'reference_id'    => $cash_tx_id,
                'transaction_type' => 'gold_purchase',
                'status'          => 'completed',
                'tracking_code'   => $tracking_code,
                'description'     => $description,
                'ip_address'      => GDB_Security::get_client_ip(),
                'user_agent'      => substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                'created_at'      => current_time('mysql'),
            ]);
            $metal_tx_id = $wpdb->insert_id;

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            $wpdb->query('COMMIT');


            GDB_Security::log_event($user_id, 'gold_purchased', $description, 'low', [
                'gold_type_id' => $type->id, 'quantity' => $quantity, 'rial_amount' => $rial_amount, 'tracking_code' => $tracking_code
            ]);
            GDB_Security::flag_transaction_if_suspicious($cash_tx_id, $rial_amount, $user_id, 'خرید طلا از کیف پول');

            if (class_exists('GDB_Cashback')) {
                GDB_Cashback::maybe_apply($user_id, $rial_amount, 'gold', $cash_tx_id);
            }

            return [
                'quantity'        => $quantity,
                'rial_amount'     => $rial_amount,
                'rial_amount_formatted' => gdb_price_plain($rial_amount),
                'tracking_code'   => $tracking_code,
                'cash_balance'    => $cash_balance_after,
                'cash_balance_formatted' => gdb_price_plain($cash_balance_after),



                'cash_balance_display' => gdb_display_amount($cash_balance_after),
                'gold_balance'    => $gold_balance_after,
                'unit_label'      => $type->unit_label,
            ];
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('purchase_failed', __('خطا در ثبت خرید. لطفاً مجدداً تلاش کنید.', 'golden-dashboard'));
        }
    }





    private function reserve_wallet_portion($user_id, $amount_toman, $order_id, $type, $quantity, $gateway_portion_toman = 0) {
        global $wpdb;
        $table_wallet = $wpdb->prefix . 'gd_user_wallet';
        $table_trans = $wpdb->prefix . 'gd_wallet_transactions';

        $wpdb->query('START TRANSACTION');
        try {
            $balance_before = GDB_Wallet::balance($user_id);
            if ($amount_toman > $balance_before) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('insufficient_balance', __('موجودی کیف پول برای بخش انتخابی کافی نیست.', 'golden-dashboard'));
            }
            $balance_after = $balance_before - $amount_toman;

            $wpdb->update($table_wallet, [
                'balance'     => $balance_after,
                'total_debit' => $wpdb->get_var($wpdb->prepare("SELECT total_debit FROM {$table_wallet} WHERE user_id=%d", $user_id)) + $amount_toman,
                'updated_at'  => current_time('mysql'),
            ], ['user_id' => $user_id], ['%f', '%f', '%s'], ['%d']);

            $description = sprintf(
                __('کسر بخشی از مبلغ خرید %s %s طلا (%s) از کیف پول - سفارش شماره %d - پرداخت ترکیبی: %s از کیف پول + %s از درگاه پرداخت', 'golden-dashboard'),
                rtrim(rtrim(number_format($quantity, 3), '0'), '.'),
                $type->unit_label,
                $type->name,
                $order_id,
                gdb_price_plain($amount_toman),
                gdb_price_plain($gateway_portion_toman)
            );

            $wpdb->insert($table_trans, [
                'user_id'          => $user_id,
                'type'             => 'debit',
                'amount'           => $amount_toman,
                'balance_before'   => $balance_before,
                'balance_after'    => $balance_after,
                'transaction_type' => 'gold_purchase',
                'reference_id'     => $order_id,
                'description'      => $description,
                'created_by'       => $user_id,
                'ip_address'       => GDB_Security::get_client_ip(),
                'user_agent'       => substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                'status'           => 'completed',
                'meta_data'        => maybe_serialize(['gold_type_id' => $type->id, 'quantity' => $quantity, 'order_id' => $order_id]),
                'created_at'       => current_time('mysql'),
                'updated_at'       => current_time('mysql'),
            ]);

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }
            $wpdb->query('COMMIT');

            return $wpdb->insert_id;
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('reserve_failed', __('خطا در کسر مبلغ از کیف پول.', 'golden-dashboard'));
        }
    }



    public function handle_gold_order_cancelled($order_id, $from, $to, $order) {
        if ($order->get_meta('_gdb_is_gold_purchase') !== 'yes') {
            return;
        }
        if (!in_array($to, ['cancelled', 'failed', 'refunded'])) {
            return;
        }

        $user_id = $order->get_customer_id();
        global $wpdb;









        if ($order->get_meta('_gdb_gold_funds_credited') === 'yes' && $order->get_meta('_gdb_gold_reversed') !== 'yes') {
            $type_id = absint($order->get_meta('_gdb_gold_type_id'));
            $quantity = floatval($order->get_meta('_gdb_gold_quantity'));
            $type = self::get_type($type_id);

            if ($type && $quantity > 0) {
                $table_gold_wallet = $wpdb->prefix . 'gd_user_metal_wallet';
                $table_metal_trans = $wpdb->prefix . 'gd_metal_transactions';

                $wpdb->query('START TRANSACTION');
                try {
                    $gold_balance_before = self::balance($user_id, $type_id);
                    $gold_balance_after = max(0, $gold_balance_before - $quantity);

                    $wpdb->update($table_gold_wallet, [
                        'balance'    => $gold_balance_after,
                        'total_debit' => $wpdb->get_var($wpdb->prepare("SELECT total_debit FROM {$table_gold_wallet} WHERE user_id=%d AND gold_type_id=%d", $user_id, $type_id)) + $quantity,
                        'updated_at' => current_time('mysql'),
                    ], ['user_id' => $user_id, 'gold_type_id' => $type_id], ['%f', '%f', '%s'], ['%d', '%d']);

                    $wpdb->insert($table_metal_trans, [
                        'user_id'          => $user_id,
                        'gold_type_id'     => $type_id,
                        'type'             => 'debit',
                        'quantity'         => $quantity,
                        'balance_before'   => $gold_balance_before,
                        'balance_after'    => $gold_balance_after,
                        'payment_mode'     => 'reversal',
                        'reference_id'     => $order_id,
                        'transaction_type' => 'gold_purchase',
                        'status'           => 'completed',
                        'description'      => sprintf(__('برگشت %s %s طلا به دلیل لغو/ناموفق شدن سفارش شماره %d', 'golden-dashboard'), rtrim(rtrim(number_format($quantity, 3), '0'), '.'), $type->unit_label, $order_id),
                        'ip_address'       => GDB_Security::get_client_ip(),
                        'created_at'       => current_time('mysql'),
                    ]);

                    if ($wpdb->last_error) {
                        throw new Exception($wpdb->last_error);
                    }
                    $wpdb->query('COMMIT');

                    $order->update_meta_data('_gdb_gold_reversed', 'yes');
                    GDB_Security::log_event($user_id, 'gold_purchase_reversed', sprintf('برگشت %s %s طلا به دلیل لغو سفارش شماره %d', $quantity, $type->unit_label, $order_id), 'medium', ['order_id' => $order_id]);
                } catch (Exception $e) {
                    $wpdb->query('ROLLBACK');
                }
            }






            $gateway_portion_toman = gdb_storage_amount((float) $order->get_total());
            if ($gateway_portion_toman > 0 && $order->get_meta('_gdb_gateway_portion_refunded') !== 'yes') {
                $table_wallet = $wpdb->prefix . 'gd_user_wallet';
                $table_trans = $wpdb->prefix . 'gd_wallet_transactions';

                $wpdb->query('START TRANSACTION');
                try {
                    $wallet = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_wallet} WHERE user_id = %d FOR UPDATE", $user_id));
                    $balance_before = $wallet ? (float) $wallet->balance : 0;
                    $balance_after = $balance_before + $gateway_portion_toman;
                    $tracking_code = gdb_generate_tracking_code();

                    if ($wallet) {
                        $wpdb->update($table_wallet, ['balance' => $balance_after, 'updated_at' => current_time('mysql')], ['user_id' => $user_id], ['%f', '%s'], ['%d']);
                    } else {
                        $wpdb->insert($table_wallet, ['user_id' => $user_id, 'balance' => $balance_after, 'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql')], ['%d', '%f', '%s', '%s']);
                    }

                    $wpdb->insert($table_trans, [
                        'user_id'          => $user_id,
                        'type'             => 'credit',
                        'amount'           => $gateway_portion_toman,
                        'balance_before'   => $balance_before,
                        'balance_after'    => $balance_after,
                        'transaction_type' => 'gold_purchase',
                        'reference_id'     => $order_id,
                        'description'      => sprintf(__('بازگشت مبلغ پرداخت‌شده از طریق درگاه پرداخت به کیف پول، به دلیل لغو/بازگشت سفارش خرید طلا شماره %d - کد پیگیری: %s', 'golden-dashboard'), $order_id, $tracking_code),
                        'created_by'       => 0,
                        'ip_address'       => GDB_Security::get_client_ip(),
                        'status'           => 'completed',
                        'tracking_code'    => $tracking_code,
                        'created_at'       => current_time('mysql'),
                        'updated_at'       => current_time('mysql'),
                    ]);

                    if ($wpdb->last_error) {
                        throw new Exception($wpdb->last_error);
                    }
                    $wpdb->query('COMMIT');

                    $order->update_meta_data('_gdb_gateway_portion_refunded', 'yes');
                    GDB_Security::log_event($user_id, 'gold_gateway_portion_refunded', sprintf('بازگشت %s تومانِ پرداخت‌شده از درگاه به کیف پول، به دلیل لغو سفارش شماره %d', $gateway_portion_toman, $order_id), 'medium', ['order_id' => $order_id]);
                } catch (Exception $e) {
                    $wpdb->query('ROLLBACK');
                }
            }
        }


        $wallet_portion = (float) $order->get_meta('_gdb_wallet_portion_toman');
        if ($wallet_portion > 0 && $order->get_meta('_gdb_gold_reservation_refunded') !== 'yes') {
            $table_wallet = $wpdb->prefix . 'gd_user_wallet';
            $table_trans = $wpdb->prefix . 'gd_wallet_transactions';

            $wpdb->query('START TRANSACTION');
            try {
                $wallet = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_wallet} WHERE user_id = %d FOR UPDATE", $user_id));
                $balance_before = $wallet ? (float) $wallet->balance : GDB_Wallet::balance($user_id);
                $balance_after = $balance_before + $wallet_portion;
                $tracking_code = gdb_generate_tracking_code();

                $wpdb->update($table_wallet, [
                    'balance' => $balance_after,
                    'updated_at' => current_time('mysql'),
                ], ['user_id' => $user_id], ['%f', '%s'], ['%d']);

                $wpdb->insert($table_trans, [
                    'user_id'          => $user_id,
                    'type'             => 'credit',
                    'amount'           => $wallet_portion,
                    'balance_before'   => $balance_before,
                    'balance_after'    => $balance_after,
                    'transaction_type' => 'gold_purchase',
                    'reference_id'     => $order_id,
                    'description'      => sprintf(__('بازگشت مبلغ کسرشده از کیف پول به دلیل لغو/ناموفق شدن سفارش شماره %d - کد پیگیری: %s', 'golden-dashboard'), $order_id, $tracking_code),
                    'created_by'       => 0,
                    'ip_address'       => GDB_Security::get_client_ip(),
                    'status'           => 'completed',
                    'tracking_code'    => $tracking_code,
                    'created_at'       => current_time('mysql'),
                    'updated_at'       => current_time('mysql'),
                ]);

                if ($wpdb->last_error) {
                    throw new Exception($wpdb->last_error);
                }
                $wpdb->query('COMMIT');

                $order->update_meta_data('_gdb_gold_reservation_refunded', 'yes');
                GDB_Security::log_event($user_id, 'gold_reservation_refunded', sprintf('بازگشت %s تومان کسرشده از کیف پول به دلیل لغو سفارش شماره %d', $wallet_portion, $order_id), 'medium', ['order_id' => $order_id]);
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
            }
        }


        if (class_exists('GDB_Cashback')) {
            GDB_Cashback::reverse_for_order($order_id);
        }










        if ($order->get_meta('_gdb_gold_cancel_logged') !== 'yes') {
            $table_trans = $wpdb->prefix . 'gd_wallet_transactions';
            $current_balance = GDB_Wallet::balance($user_id);
            $tracking_code = gdb_generate_tracking_code();

            $status_action_labels = [
                'cancelled' => __('لغو شد', 'golden-dashboard'),
                'failed'    => __('ناموفق بود', 'golden-dashboard'),
                'refunded'  => __('بازگشت داده شد', 'golden-dashboard'),
            ];
            $status_action_label = isset($status_action_labels[$to]) ? $status_action_labels[$to] : $to;

            $wpdb->insert($table_trans, [
                'user_id'          => $user_id,
                'type'             => 'credit',
                'amount'           => 0,
                'balance_before'   => $current_balance,
                'balance_after'    => $current_balance,
                'transaction_type' => 'gold_purchase',
                'reference_id'     => $order_id,
                'description'      => sprintf(__('سفارش خرید طلا شماره %d %s - کد پیگیری: %s', 'golden-dashboard'), $order_id, $status_action_label, $tracking_code),
                'created_by'       => 0,
                'ip_address'       => GDB_Security::get_client_ip(),
                'status'           => $to,
                'tracking_code'    => $tracking_code,
                'created_at'       => current_time('mysql'),
                'updated_at'       => current_time('mysql'),
            ]);

            $order->update_meta_data('_gdb_gold_cancel_logged', 'yes');
        }

        $order->save();
    }



    private function create_gateway_order($user_id, $type, $quantity, $gateway_portion_display, $wallet_portion_toman = 0, $wallet_reserve_tx_id = 0, $return_url = '') {
        if (!function_exists('wc_create_order')) {
            return new WP_Error('woocommerce_unavailable', __('ووکامرس در حال حاضر در دسترس نیست.', 'golden-dashboard'));
        }

        $product = wc_get_product($type->wc_product_id);
        if (!$product) {
            return new WP_Error('product_not_found', __('محصول مرجع قیمت یافت نشد.', 'golden-dashboard'));
        }

        try {
            $order = wc_create_order([
                'customer_id' => $user_id,
                'created_via' => 'golden-dashboard-gold-wallet',
            ]);
            if (is_wp_error($order)) {
                throw new Exception($order->get_error_message());
            }






            $item_id = $order->add_product($product, 1, [
                'subtotal' => $gateway_portion_display,
                'total'    => $gateway_portion_display,
            ]);
            if (!$item_id) {
                throw new Exception('add_product failed');
            }

            $order->set_currency(get_woocommerce_currency());
            $order->calculate_totals();

            $order->update_meta_data('_gdb_is_gold_purchase', 'yes');
            $order->update_meta_data('_gdb_gold_type_id', $type->id);
            $order->update_meta_data('_gdb_gold_quantity', $quantity);
            $order->update_meta_data('_gdb_wallet_portion_toman', $wallet_portion_toman);
            $order->update_meta_data('_gdb_wallet_reserve_tx_id', $wallet_reserve_tx_id);


            if (empty($return_url)) {
                $return_url = home_url('/');
            }
            $order->update_meta_data('_gdb_return_url', $return_url);

            error_log('Saving return_url: ' . $return_url . ' for order ' . $order->get_id());

            $order->set_status('pending', __('سفارش خرید طلا ایجاد شد و در انتظار پرداخت است.', 'golden-dashboard'));
            $order->save();

            return $order;
        } catch (Exception $e) {
            return new WP_Error('order_failed', __('خطا در ایجاد سفارش خرید طلا.', 'golden-dashboard'));
        }
    }



    public function filter_gold_return_url($return_url, $order) {

        error_log('=== filter_gold_return_url (priority 999) called ===');
        error_log('Order ID: ' . $order->get_id());
        error_log('Original return_url: ' . $return_url);

        if (!$order instanceof WC_Order) {
            error_log('Not a WC_Order object');
            return $return_url;
        }

        if ($order->get_meta('_gdb_is_gold_purchase') !== 'yes') {
            error_log('Not a gold purchase order');
            return $return_url;
        }

        $target = $order->get_meta('_gdb_return_url');
        error_log('_gdb_return_url from meta: ' . ($target ?: 'EMPTY'));

        if (empty($target)) {
            $target = home_url('/');
            error_log('Using fallback target: ' . $target);
        }


        $target = remove_query_arg(['gdb_gold_result', 'order_id', 'key', 'gdb_message'], $target);
        $new_url = add_query_arg([
            'gdb_gold_result' => 1,
            'order_id'        => $order->get_id(),
            'key'             => $order->get_order_key(),
        ], $target);

        error_log('Final return URL: ' . $new_url);
        return $new_url;
    }

    public function ajax_get_gold_order_summary() {

        error_log('=== ajax_get_gold_order_summary called ===');
        error_log('POST data: ' . print_r($_POST, true));

        check_ajax_referer('gdb_nonce', 'nonce');

        if (!is_user_logged_in()) {
            error_log('User not logged in.');
            wp_send_json_error(['message' => __('برای مشاهده این بخش ابتدا وارد حساب کاربری خود شوید.', 'golden-dashboard')], 403);
        }
        if (!function_exists('wc_get_order')) {
            error_log('WooCommerce not available.');
            wp_send_json_error(['message' => __('ووکامرس در دسترس نیست.', 'golden-dashboard')], 500);
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $key = isset($_POST['key']) ? sanitize_text_field(wp_unslash($_POST['key'])) : '';

        error_log("Order ID: $order_id, Key: $key");

        $order = $order_id ? wc_get_order($order_id) : false;
        if (!$order || !hash_equals((string) $order->get_order_key(), (string) $key)) {
            error_log('Order not found or key mismatch.');
            wp_send_json_error(['message' => __('سفارش یافت نشد.', 'golden-dashboard')], 404);
        }
        if ((int) $order->get_customer_id() !== get_current_user_id()) {
            error_log('User does not have permission to view this order.');
            wp_send_json_error(['message' => __('شما اجازه دسترسی به این سفارش را ندارید.', 'golden-dashboard')], 403);
        }
        if ($order->get_meta('_gdb_is_gold_purchase') !== 'yes') {
            error_log('Order is not a gold purchase.');
            wp_send_json_error(['message' => __('این سفارش مربوط به خرید طلا نیست.', 'golden-dashboard')], 400);
        }

        $is_paid = $order->is_paid() || $order->has_status(['processing', 'completed']);
        $type = self::get_type(absint($order->get_meta('_gdb_gold_type_id')));
        $quantity = floatval($order->get_meta('_gdb_gold_quantity'));
        $wallet_portion = (float) $order->get_meta('_gdb_wallet_portion_toman');
        $tracking_code = $order->get_meta('_gdb_gold_tracking_code');

        error_log('Sending success response.');
        wp_send_json_success([
            'is_paid'        => $is_paid,
            'order_number'   => $order->get_order_number(),
            'status'         => wc_get_order_status_name($order->get_status()),
            'status_key'     => $order->get_status(),
            'quantity'       => $quantity,
            'unit_label'     => $type ? $type->unit_label : '',
            'total'          => html_entity_decode(wp_strip_all_tags($order->get_formatted_order_total()), ENT_QUOTES, get_bloginfo('charset')),
            'wallet_portion_formatted' => $wallet_portion > 0 ? gdb_price_plain($wallet_portion) : '',
            'gateway_portion_formatted' => wp_kses_post(wc_price((float) $order->get_total())),
            'date'           => wc_format_datetime($order->get_date_created(), get_option('date_format') . ' H:i:s'),
            'tracking_code'  => $tracking_code,
        ]);
    }

    public function credit_gold_on_order_complete($order_id) {
        error_log('=== credit_gold_on_order_complete called for order ' . $order_id);
        $order = wc_get_order($order_id);
        if (!$order || $order->get_meta('_gdb_is_gold_purchase') !== 'yes') {
            error_log('Not a gold purchase order, skipping.');
            return;
        }
        if ($order->get_meta('_gdb_gold_funds_credited') === 'yes') {
            error_log('Already credited, skipping.');
            return;
        }

        $type_id = absint($order->get_meta('_gdb_gold_type_id'));
        $quantity = floatval($order->get_meta('_gdb_gold_quantity'));
        $type = self::get_type($type_id);
        if (!$type || $quantity <= 0) {
            error_log('Invalid type or quantity.');
            return;
        }

        $user_id = $order->get_customer_id();
        $gateway_amount_display = (float) $order->get_total();
        $gateway_amount_toman = gdb_storage_amount($gateway_amount_display);
        $wallet_portion_toman = (float) $order->get_meta('_gdb_wallet_portion_toman');
        $rial_amount = $gateway_amount_toman + $wallet_portion_toman;
        $price = $quantity > 0 ? $rial_amount / $quantity : 0;

        global $wpdb;
        self::ensure_user_row($user_id, $type_id);

        $wpdb->query('START TRANSACTION');
        try {
            $tracking_code = function_exists('gdb_generate_tracking_code') ? gdb_generate_tracking_code() : wp_generate_password(12, false);
            $gold_balance_before = self::balance($user_id, $type_id);
            $gold_balance_after = $gold_balance_before + $quantity;

            $table_gold_wallet = $wpdb->prefix . 'gd_user_metal_wallet';
            $wpdb->update($table_gold_wallet, [
                'balance'      => $gold_balance_after,
                'total_credit' => $wpdb->get_var($wpdb->prepare("SELECT total_credit FROM {$table_gold_wallet} WHERE user_id=%d AND gold_type_id=%d", $user_id, $type_id)) + $quantity,
                'updated_at'   => current_time('mysql'),
            ], ['user_id' => $user_id, 'gold_type_id' => $type_id], ['%f', '%f', '%s'], ['%d', '%d']);

            if ($wallet_portion_toman > 0) {
                $description = sprintf(
                    __('خرید %s %s طلا (%s) - سفارش شماره %d - پرداخت ترکیبی: %s از کیف پول + %s از درگاه پرداخت - کد پیگیری: %s', 'golden-dashboard'),
                    rtrim(rtrim(number_format($quantity, 3), '0'), '.'),
                    $type->unit_label,
                    $type->name,
                    $order_id,
                    gdb_price_plain($wallet_portion_toman),
                    gdb_price_plain($gateway_amount_toman),
                    $tracking_code
                );
            } else {
                $description = sprintf(
                    __('خرید %s %s طلا (%s) از طریق درگاه پرداخت - سفارش شماره %d - کد پیگیری: %s', 'golden-dashboard'),
                    rtrim(rtrim(number_format($quantity, 3), '0'), '.'),
                    $type->unit_label,
                    $type->name,
                    $order_id,
                    $tracking_code
                );
            }
            $payment_mode_label = $wallet_portion_toman > 0 ? 'hybrid' : 'gateway';

            $table_metal_trans = $wpdb->prefix . 'gd_metal_transactions';
            $wpdb->insert($table_metal_trans, [
                'user_id'          => $user_id,
                'gold_type_id'     => $type_id,
                'type'             => 'credit',
                'quantity'         => $quantity,
                'price_per_unit'   => $price,
                'rial_amount'      => $rial_amount,
                'balance_before'   => $gold_balance_before,
                'balance_after'    => $gold_balance_after,
                'payment_mode'     => $payment_mode_label,
                'reference_id'     => $order_id,
                'transaction_type' => 'gold_purchase',
                'status'           => 'completed',
                'tracking_code'    => $tracking_code,
                'description'      => $description,
                'ip_address'       => GDB_Security::get_client_ip(),
                'created_at'       => current_time('mysql'),
            ]);
            $metal_tx_id = $wpdb->insert_id;

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            $wpdb->query('COMMIT');





            $table_cash_trans = $wpdb->prefix . 'gd_wallet_transactions';
            $current_balance = GDB_Wallet::balance($user_id);
            $wpdb->insert($table_cash_trans, [
                'user_id'          => $user_id,
                'type'             => 'credit',
                'amount'           => $gateway_amount_toman,
                'balance_before'   => $current_balance,
                'balance_after'    => $current_balance,
                'transaction_type' => 'gold_purchase',
                'reference_id'     => $order_id,
                'description'      => sprintf(
                    __('پرداخت %s از طریق درگاه پرداخت برای خرید %s %s طلا (%s) - سفارش شماره %d - کد پیگیری: %s', 'golden-dashboard'),
                    gdb_price_plain($gateway_amount_toman),
                    rtrim(rtrim(number_format($quantity, 3), '0'), '.'),
                    $type->unit_label,
                    $type->name,
                    $order_id,
                    $tracking_code
                ),
                'created_by'       => $user_id,
                'ip_address'       => GDB_Security::get_client_ip(),
                'status'           => 'completed',
                'tracking_code'    => $tracking_code,
                'created_at'       => current_time('mysql'),
                'updated_at'       => current_time('mysql'),
            ]);

            $order->update_meta_data('_gdb_gold_funds_credited', 'yes');
            $order->update_meta_data('_gdb_gold_tracking_code', $tracking_code);
            $order->save();
            $order->add_order_note(sprintf('کیف پول طلای کاربر به میزان %s %s شارژ شد. کد پیگیری: %s', $quantity, $type->unit_label, $tracking_code));

            GDB_Security::log_event($user_id, 'gold_purchased', $description, 'low', ['order_id' => $order_id, 'quantity' => $quantity, 'rial_amount' => $rial_amount]);
            GDB_Security::flag_transaction_if_suspicious($metal_tx_id, $rial_amount, $user_id, 'خرید طلا از درگاه پرداخت');

            if (class_exists('GDB_Cashback')) {
                GDB_Cashback::maybe_apply($user_id, $rial_amount, 'gold', $order_id);
            }

            error_log('Gold purchase completed successfully.');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Error in credit_gold_on_order_complete: ' . $e->getMessage());
            wc_get_logger()->error('خطا در تکمیل خرید طلا: ' . $e->getMessage(), ['source' => 'gdb-gold-wallet']);
        }
    }

    public function handle_gold_order_status_change($order_id, $from, $to, $order) {
        if ($order->get_meta('_gdb_is_gold_purchase') !== 'yes') {
            return;
        }
        if (in_array($to, ['completed', 'processing']) && $order->get_meta('_gdb_gold_funds_credited') !== 'yes') {
            $this->credit_gold_on_order_complete($order_id);
        }
    }



    public function ajax_get_price() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'gdb_gold_wallet_nonce')) {
            wp_send_json_error(['message' => __('توکن امنیتی نامعتبر است.', 'golden-dashboard')]);
        }

        $type_id = absint($_POST['type_id'] ?? 0);
        $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
        $type = self::get_type($type_id);

        if (!$type || $type->status !== 'active') {
            wp_send_json_error(['message' => __('این نوع کیف پول طلا در دسترس نیست.', 'golden-dashboard')]);
        }

        $price = self::get_live_price($type);
        $total = $price * max(0, $quantity);

        wp_send_json_success([
            'price_per_unit'   => $price,
            'price_formatted'  => gdb_wc_price_plain($price),
            'total'            => $total,
            'total_formatted'  => gdb_wc_price_plain($total),
        ]);
    }

    public function ajax_process_purchase() {
        error_log('=== ajax_process_purchase called ===');
        if (!isset($_POST['gdb_gold_wallet_nonce']) || !wp_verify_nonce($_POST['gdb_gold_wallet_nonce'], 'gdb_gold_wallet_nonce')) {
            error_log('Invalid nonce.');
            wp_send_json_error(['message' => __('توکن امنیتی نامعتبر است.', 'golden-dashboard')]);
        }

        if (!is_user_logged_in()) {
            error_log('User not logged in.');
            wp_send_json_error(['message' => __('برای خرید طلا ابتدا وارد حساب کاربری خود شوید.', 'golden-dashboard')]);
        }

        $user_id = get_current_user_id();
        $type_id = absint($_POST['type_id'] ?? 0);
        $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;

        $type = self::get_type($type_id);
        if (!$type || $type->status !== 'active') {
            error_log('Type not found or inactive.');
            wp_send_json_error(['message' => __('این نوع کیف پول طلا در دسترس نیست.', 'golden-dashboard')]);
        }

        if ($quantity <= 0) {
            error_log('Invalid quantity.');
            wp_send_json_error(['message' => __('مقدار وارد شده معتبر نیست.', 'golden-dashboard')]);
        }
        if ($type->min_amount > 0 && $quantity < $type->min_amount) {
            error_log('Below minimum quantity.');
            wp_send_json_error(['message' => sprintf(__('حداقل مقدار خرید %s %s است.', 'golden-dashboard'), $type->min_amount, $type->unit_label)]);
        }
        if ($type->max_amount > 0 && $quantity > $type->max_amount) {
            error_log('Above maximum quantity.');
            wp_send_json_error(['message' => sprintf(__('حداکثر مقدار خرید %s %s است.', 'golden-dashboard'), $type->max_amount, $type->unit_label)]);
        }


        if (GDB_Security::is_ip_blocked(GDB_Security::get_client_ip())) {
            GDB_Security::log_event($user_id, 'blocked_ip_attempt', 'تلاش برای خرید طلا از یک IP مسدود شده.', 'high', ['action' => 'gold_purchase']);
            wp_send_json_error(['message' => __('امکان انجام این عملیات از این آدرس وجود ندارد.', 'golden-dashboard')]);
        }

        $rate_limit_check = GDB_Security::check_rate_limit('user_' . $user_id, 'gold_purchase');
        if (is_wp_error($rate_limit_check)) {
            wp_send_json_error(['message' => $rate_limit_check->get_error_message()]);
        }

        $daily_limit_check = GDB_Security::check_daily_transaction_limit($user_id);
        if (is_wp_error($daily_limit_check)) {
            wp_send_json_error(['message' => $daily_limit_check->get_error_message()]);
        }


        $price_display = self::get_live_price($type);
        if ($price_display <= 0) {
            error_log('Price not available.');
            wp_send_json_error(['message' => __('قیمت لحظه‌ای این محصول در دسترس نیست.', 'golden-dashboard')]);
        }

        $price_toman = gdb_storage_amount($price_display);
        $rial_amount_toman = $price_toman * $quantity;

        $max_amount_check = GDB_Security::check_max_transaction_amount($rial_amount_toman);
        if (is_wp_error($max_amount_check)) {
            wp_send_json_error(['message' => $max_amount_check->get_error_message()]);
        }

        $cash_balance = GDB_Wallet::balance($user_id);
        $use_wallet_balance = !empty($_POST['use_wallet_balance']);





        if ($type->payment_mode !== 'wallet_or_gateway') {
            if ($rial_amount_toman > $cash_balance) {
                error_log('Insufficient balance and payment mode is wallet_only.');
                wp_send_json_error(['message' => sprintf(
                    __('موجودی کیف پول شما کافی نیست. مبلغ لازم: %s، موجودی فعلی: %s. لطفاً ابتدا کیف پول خود را شارژ کنید.', 'golden-dashboard'),
                    gdb_price_plain($rial_amount_toman),
                    gdb_price_plain($cash_balance)
                )]);
            }
            error_log('Purchasing from wallet (wallet_only type).');
            $result = $this->purchase_from_wallet($user_id, $type, $quantity, $price_toman, $rial_amount_toman);
            if (is_wp_error($result)) {
                error_log('Wallet purchase failed: ' . $result->get_error_message());
                wp_send_json_error(['message' => $result->get_error_message()]);
            }
            wp_send_json_success(array_merge(['mode' => 'wallet'], $result));
        }










        if ($use_wallet_balance && $rial_amount_toman <= $cash_balance) {
            error_log('Purchasing from wallet (user opted in and balance sufficient).');
            $result = $this->purchase_from_wallet($user_id, $type, $quantity, $price_toman, $rial_amount_toman);
            if (is_wp_error($result)) {
                error_log('Wallet purchase failed: ' . $result->get_error_message());
                wp_send_json_error(['message' => $result->get_error_message()]);
            }
            wp_send_json_success(array_merge(['mode' => 'wallet'], $result));
        }

        $wallet_portion_toman = ($use_wallet_balance && $cash_balance > 0) ? min($cash_balance, $rial_amount_toman) : 0;
        $gateway_portion_toman = $rial_amount_toman - $wallet_portion_toman;


        $return_url = gdb_get_safe_return_url('gdb_return_url');
        if (empty($return_url)) {
            $return_url = home_url('/');
        }
        error_log('Return URL: ' . $return_url);

        $order = $this->create_gateway_order(
            $user_id,
            $type,
            $quantity,
            gdb_display_amount($gateway_portion_toman),
            0,
            0,
            $return_url
        );
        if (is_wp_error($order)) {
            error_log('Order creation failed: ' . $order->get_error_message());
            wp_send_json_error(['message' => $order->get_error_message()]);
        }

        if ($wallet_portion_toman > 0) {
            $reserve_result = $this->reserve_wallet_portion($user_id, $wallet_portion_toman, $order->get_id(), $type, $quantity, $gateway_portion_toman);
            if (is_wp_error($reserve_result)) {
                $order->update_status('cancelled', __('کسر مبلغ از کیف پول ناموفق بود.', 'golden-dashboard'));
                wp_send_json_error(['message' => $reserve_result->get_error_message()]);
            }
            $order->update_meta_data('_gdb_wallet_portion_toman', $wallet_portion_toman);
            $order->update_meta_data('_gdb_wallet_reserve_tx_id', $reserve_result);
            $order->save();
        }

        error_log('Redirecting to payment gateway.');
        wp_send_json_success([
            'mode'         => 'gateway',
            'redirect_url' => $order->get_checkout_payment_url(),
        ]);
    }
}
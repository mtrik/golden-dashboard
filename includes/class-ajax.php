<?php

if (!defined('ABSPATH')) {
    exit;
}

class GDB_Ajax
{
    public function __construct()
    {
        add_action('wp_ajax_gdb_filter_transactions', [$this, 'filter_transactions']);
        add_action('wp_ajax_gdb_get_topup_order_summary', [$this, 'get_topup_order_summary']);
        add_action('wp_ajax_gdb_process_withdraw', [$this, 'process_withdraw']);
        add_action('wp_ajax_gdb_get_recent_transactions', [$this, 'get_recent_transactions']);
        add_action('wp_ajax_gdb_get_chart_data', [$this, 'get_chart_data']);
        add_action('wp_ajax_gdb_export_transactions_csv', [$this, 'export_transactions_csv']);
    }



    public function filter_transactions()
    {
        check_ajax_referer('gdb_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('برای مشاهده این بخش ابتدا وارد حساب کاربری شوید.', 'golden-dashboard')], 403);
        }

        $user_id = get_current_user_id();

        $filter_type = isset($_POST['filter_type']) ? sanitize_text_field(wp_unslash($_POST['filter_type'])) : '';
        if (!in_array($filter_type, ['', 'credit', 'debit'], true)) {
            $filter_type = '';
        }

        $allowed_transaction_types = ['', 'admin_credit', 'admin_debit', 'order_payment', 'order_refund', 'withdraw', 'withdraw_request', 'gold_purchase', 'cashback', 'cashback_reversal', 'gold_buy', 'gold_sell'];
        $filter_transaction_type = isset($_POST['filter_transaction_type']) ? sanitize_text_field(wp_unslash($_POST['filter_transaction_type'])) : '';
        if (!in_array($filter_transaction_type, $allowed_transaction_types, true)) {
            $filter_transaction_type = '';
        }

        $page = isset($_POST['history_page']) ? absint($_POST['history_page']) : 1;
        $per_page = isset($_POST['history_per_page']) ? absint($_POST['history_per_page']) : 20;
        if ($per_page < 1) $per_page = 20;
        if ($page < 1) $page = 1;

        $columns = [
            'row_number'     => !empty($_POST['show_history_row_number']),
            'date'           => !empty($_POST['show_history_date']),
            'type'           => !empty($_POST['show_history_type']),
            'amount'         => !empty($_POST['show_history_amount']),
            'balance_before' => !empty($_POST['show_history_balance_before']),
            'balance_after'  => !empty($_POST['show_history_balance_after']),
            'status'         => !empty($_POST['show_history_status']),
            'fee'            => true,
            'description'    => !empty($_POST['show_history_description']),
        ];

        $result = gdb_get_wallet_history_paginated($user_id, $filter_type, $filter_transaction_type, $page, $per_page);

        ob_start();
        if ($result['items']) {
            gdb_render_history_table_paginated(
                $result['items'],
                $columns,
                $result['total'],
                $result['pages'],
                $page,
                $per_page,
                $filter_type,
                $filter_transaction_type,
                true
            );
        } else {
            echo gdb_empty(__('هیچ تراکنشی با این فیلترها یافت نشد.', 'golden-dashboard'));
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'html'        => $html,
            'filter_type' => $filter_type,
            'filter_transaction_type' => $filter_transaction_type,
            'page'        => $page,
            'per_page'    => $per_page,
            'total'       => $result['total'],
            'pages'       => $result['pages'],
        ]);
    }



    public function get_topup_order_summary()
    {
        check_ajax_referer('gdb_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('برای مشاهده این بخش ابتدا وارد حساب کاربری خود شوید.', 'golden-dashboard')], 403);
        }

        if (!function_exists('wc_get_order')) {
            wp_send_json_error(['message' => __('ووکامرس در دسترس نیست.', 'golden-dashboard')], 500);
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $key      = isset($_POST['key']) ? sanitize_text_field(wp_unslash($_POST['key'])) : '';

        $order = $order_id ? wc_get_order($order_id) : false;
        if (!$order || !hash_equals((string) $order->get_order_key(), (string) $key)) {
            wp_send_json_error(['message' => __('سفارش یافت نشد.', 'golden-dashboard')], 404);
        }
        if ((int) $order->get_customer_id() !== get_current_user_id()) {
            wp_send_json_error(['message' => __('شما اجازه دسترسی به این سفارش را ندارید.', 'golden-dashboard')], 403);
        }
        if ($order->get_meta('_gdb_is_topup') !== 'yes') {
            wp_send_json_error(['message' => __('این سفارش مربوط به شارژ کیف پول نیست.', 'golden-dashboard')], 400);
        }

        $is_paid = $order->is_paid() || $order->has_status(['processing', 'completed']);
        $formatted_total = html_entity_decode(wp_strip_all_tags($order->get_formatted_order_total()), ENT_QUOTES, get_bloginfo('charset'));

        $tracking_code = $order->get_meta('_gdb_topup_tracking_code');
        if (empty($tracking_code)) {
            global $wpdb;
            $table = $wpdb->prefix . 'gd_wallet_transactions';
            $tx = $wpdb->get_var($wpdb->prepare(
                "SELECT tracking_code FROM {$table} WHERE reference_id = %d AND transaction_type = 'order_payment'",
                $order_id
            ));
            if ($tx) {
                $tracking_code = $tx;
            }
        }

        wp_send_json_success([
            'is_paid'        => $is_paid,
            'order_number'   => $order->get_order_number(),
            'status'         => wc_get_order_status_name($order->get_status()),
            'status_key'     => $order->get_status(),
            'total'          => trim(preg_replace('/\x{00A0}/u', ' ', $formatted_total)),
            'date'           => wc_format_datetime($order->get_date_created(), get_option('date_format') . ' H:i:s'),
            'payment_method' => $order->get_payment_method_title(),
            'tracking_code'  => $tracking_code,
        ]);
    }



    public function process_withdraw()
    {
        check_ajax_referer('gdb_withdraw_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('لطفاً وارد شوید.', 'golden-dashboard')], 403);
        }

        $user_id = get_current_user_id();


        if (GDB_Security::is_ip_blocked(GDB_Security::get_client_ip())) {
            GDB_Security::log_event($user_id, 'blocked_ip_attempt', 'تلاش برای برداشت از کیف پول از یک IP مسدود شده.', 'high', ['action' => 'wallet_withdraw']);
            wp_send_json_error(['message' => __('امکان انجام این عملیات از این آدرس وجود ندارد.', 'golden-dashboard')]);
        }

        $rate_limit_check = GDB_Security::check_rate_limit('user_' . $user_id, 'wallet_withdraw');
        if (is_wp_error($rate_limit_check)) {
            wp_send_json_error(['message' => $rate_limit_check->get_error_message()]);
        }


        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        if ($amount <= 0) {
            wp_send_json_error(['message' => __('مبلغ وارد شده معتبر نیست.', 'golden-dashboard')]);
        }


        $amount_storage = gdb_storage_amount($amount);







        $posted_min = isset($_POST['min_amount']) ? (float) $_POST['min_amount'] : 0;
        $posted_max = isset($_POST['max_amount']) ? (float) $_POST['max_amount'] : 0;

        $min_amount = $posted_min > 0 ? $posted_min : (float) get_option('gdb_withdraw_min_amount', 1000);
        $max_amount = $posted_max > 0 ? $posted_max : (float) get_option('gdb_withdraw_max_amount', 50000000);

        if ($amount_storage < $min_amount) {
            wp_send_json_error(['message' => sprintf(__('حداقل مبلغ برداشت %s است.', 'golden-dashboard'), gdb_price_plain($min_amount))]);
        }
        if ($amount_storage > $max_amount) {
            wp_send_json_error(['message' => sprintf(__('حداکثر مبلغ برداشت %s است.', 'golden-dashboard'), gdb_price_plain($max_amount))]);
        }

        $daily_limit_check = GDB_Security::check_daily_transaction_limit($user_id);
        if (is_wp_error($daily_limit_check)) {
            wp_send_json_error(['message' => $daily_limit_check->get_error_message()]);
        }

        $max_amount_check = GDB_Security::check_max_transaction_amount($amount_storage);
        if (is_wp_error($max_amount_check)) {
            wp_send_json_error(['message' => $max_amount_check->get_error_message()]);
        }

        $balance = GDB_Wallet::balance($user_id);
        if ($amount_storage > $balance) {
            wp_send_json_error(['message' => __('موجودی کیف پول شما کافی نیست.', 'golden-dashboard')]);
        }

        $result = GDB_Withdraw_Request::create($user_id, $amount_storage, [], $min_amount, $max_amount);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        if (is_numeric($result)) {
            GDB_Security::flag_transaction_if_suspicious($result, $amount_storage, $user_id, 'درخواست برداشت');
        }

        $tracking_code = '';
        if (is_numeric($result)) {
            global $wpdb;
            $tracking_code = $wpdb->get_var($wpdb->prepare(
                "SELECT tracking_code FROM " . $wpdb->prefix . "gd_wallet_transactions WHERE id = %d",
                $result
            ));
        }







        $balance_after = GDB_Wallet::balance($user_id);

        wp_send_json_success([
            'message' => __('درخواست برداشت شما با موفقیت ثبت شد و در انتظار تأیید مدیر است.', 'golden-dashboard'),
            'request_id' => $result,
            'tracking_code' => $tracking_code,
            'balance' => $balance_after,
            'balance_formatted' => gdb_price_plain($balance_after),


            'balance_display' => gdb_display_amount($balance_after),
            'amount_formatted' => gdb_price_plain($amount_storage),
        ]);
    }



    public function get_recent_transactions()
    {
        check_ajax_referer('gdb_withdraw_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('لطفاً وارد شوید.', 'golden-dashboard')], 403);
        }

        $user_id = get_current_user_id();
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 10;
        if ($limit < 1) {
            $limit = 10;
        }

        $transactions = GDB_Wallet::get_transactions($user_id, $limit);
        $pending_requests = GDB_Wallet::get_transactions_by_type_and_status($user_id, 'withdraw_request', 'pending');

        ob_start();

        if ($transactions) {
            $columns = [
                'row_number'     => true,
                'date'           => true,
                'type'           => true,
                'amount'         => true,
                'balance_before' => true,
                'balance_after'  => true,
                'status'         => true,
                'fee'            => true,
                'description'    => true,
            ];

            $total = count($transactions);
            $pages = 1;
            $current_page = 1;
            $per_page = $total;

            gdb_render_history_table_paginated(
                $transactions,
                $columns,
                $total,
                $pages,
                $current_page,
                $per_page,
                '',
                '',
                false
            );
        } else {
            echo gdb_empty(__('هیچ تراکنشی یافت نشد.', 'golden-dashboard'));
        }

        $html = ob_get_clean();

        $has_pending = !empty($pending_requests);
        wp_send_json_success([
            'html' => $html,
            'balance' => GDB_Wallet::balance($user_id),
            'balance_formatted' => gdb_price_plain(GDB_Wallet::balance($user_id)),


            'balance_display' => gdb_display_amount(GDB_Wallet::balance($user_id)),
            'has_pending' => $has_pending,
            'pending_requests' => $pending_requests,
        ]);
    }



    public function get_chart_data()
    {
        check_ajax_referer('gdb_filter_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('دسترسی غیرمجاز', 'golden-dashboard')], 403);
        }

        $range = isset($_POST['range']) ? sanitize_text_field($_POST['range']) : '7days';
        $days = 7;

        switch ($range) {
            case 'today':
                $days = 1;
                break;
            case '7days':
                $days = 7;
                break;
            case '30days':
                $days = 30;
                break;
            case '90days':
                $days = 90;
                break;
            case 'all':
            default:
                $days = 9999;
                break;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_transactions';

        if ($days >= 9999) {
            $sql = "SELECT DATE(created_at) as date, COUNT(*) as count FROM {$table} GROUP BY DATE(created_at) ORDER BY date DESC";
            $results = $wpdb->get_results($sql);
        } else {
            $start_date = date('Y-m-d', strtotime("-$days days"));
            $sql = $wpdb->prepare(
                "SELECT DATE(created_at) as date, COUNT(*) as count FROM {$table} WHERE created_at >= %s GROUP BY DATE(created_at) ORDER BY date DESC",
                $start_date
            );
            $results = $wpdb->get_results($sql);
        }

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                'date' => $row->date,
                'count' => (int) $row->count,
            ];
        }

        wp_send_json_success([
            'data' => $data,
            'range' => $range,
        ]);
    }



    public function export_transactions_csv()
    {
        check_ajax_referer('gdb_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('لطفاً وارد شوید.', 'golden-dashboard')], 403);
        }

        $user_id = get_current_user_id();

        $filter_type = isset($_POST['filter_type']) ? sanitize_text_field(wp_unslash($_POST['filter_type'])) : '';
        $filter_transaction_type = isset($_POST['filter_transaction_type']) ? sanitize_text_field(wp_unslash($_POST['filter_transaction_type'])) : '';

        $transactions = gdb_get_wallet_history($user_id, $filter_type, $filter_transaction_type);

        echo "\xEF\xBB\xBF";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transactions-' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, [
            __('ردیف', 'golden-dashboard'),
            __('تاریخ', 'golden-dashboard'),
            __('نوع تراکنش', 'golden-dashboard'),
            __('مبلغ', 'golden-dashboard'),
            __('کارمزد', 'golden-dashboard'),
            __('موجودی قبل', 'golden-dashboard'),
            __('موجودی بعد', 'golden-dashboard'),
            __('وضعیت', 'golden-dashboard'),
            __('توضیحات', 'golden-dashboard'),
        ]);

        $gdb_csv_safe = function ($value) {
            $value = (string) $value;
            if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
                return "'" . $value;
            }
            return $value;
        };

        $row_num = 1;
        foreach ($transactions as $tx) {
            $is_credit = ($tx->type === 'credit');
            fputcsv($output, [
                $row_num++,
                gdb_date_jalali($tx->created_at, true),
                gdb_transaction_label($tx, $is_credit),
                gdb_price_plain($tx->amount),
                isset($tx->fee_amount) && $tx->fee_amount > 0 ? gdb_price_plain($tx->fee_amount) : '',
                isset($tx->balance_before) ? gdb_price_plain($tx->balance_before) : '',
                gdb_price_plain($tx->balance_after),
                isset($tx->status) ? $tx->status : 'completed',
                $gdb_csv_safe($tx->description),
            ]);
        }

        fclose($output);
        exit;
    }
}

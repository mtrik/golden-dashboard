<?php



if (!defined('ABSPATH')) {
    exit;
}

class GDB_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_post_gdb_approve_withdraw', [$this, 'handle_approve']);
        add_action('admin_post_gdb_reject_withdraw', [$this, 'handle_reject']);
        add_action('admin_post_gdb_save_settings', [$this, 'handle_save_settings']);
        add_action('admin_post_gdb_save_security_settings', [$this, 'handle_save_security_settings']);
        add_action('admin_post_gdb_block_ip', [$this, 'handle_block_ip']);
        add_action('admin_post_gdb_unblock_ip', [$this, 'handle_unblock_ip']);
        add_action('admin_post_gdb_save_gold_type', [$this, 'handle_save_gold_type']);
        add_action('admin_post_gdb_delete_gold_type', [$this, 'handle_delete_gold_type']);
        add_action('admin_post_gdb_save_cashback_settings', [$this, 'handle_save_cashback_settings']);
        add_action('admin_post_gdb_update_transaction', [$this, 'handle_update_transaction']);
        add_action('admin_post_gdb_manual_credit', [$this, 'handle_manual_credit']);
        add_action('admin_post_gdb_delete_manual_transaction', [$this, 'handle_delete_manual_transaction']);
        add_action('wp_ajax_gdb_filter_withdraw_requests', [$this, 'handle_ajax_filter']);
        add_action('wp_ajax_gdb_search_users', [$this, 'ajax_search_users']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_gdb_export_transactions_csv', [$this, 'export_transactions_csv']);
        add_action('pre_get_posts', [$this, 'filter_orders_list_by_gdb_params']);
        add_filter('woocommerce_order_query_args', [$this, 'filter_hpos_orders_by_gdb_params']);
        add_filter('parent_file', [$this, 'fix_hidden_page_menu_highlight']);
        add_filter('submenu_file', [$this, 'fix_hidden_page_submenu_highlight']);
    }



    public function fix_hidden_page_menu_highlight($parent_file) {
        global $plugin_page;
        if (in_array($plugin_page, ['gdb-transaction-detail', 'gdb-security-log'], true)) {
            $parent_file = 'gdb-dashboard';
        }
        return $parent_file;
    }



    public function fix_hidden_page_submenu_highlight($submenu_file) {
        global $plugin_page;
        if ($plugin_page === 'gdb-transaction-detail') {
            
            
            $referer = wp_get_referer();
            if ($referer && strpos($referer, 'page=gdb-withdraw-requests') !== false) {
                return 'gdb-withdraw-requests';
            }
            return 'gdb-transactions-history';
        }
        if ($plugin_page === 'gdb-security-log') {
            return 'gdb-settings';
        }
        return $submenu_file;
    }



    public function filter_orders_list_by_gdb_params($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        global $pagenow;
        if ($pagenow !== 'edit.php' || $query->get('post_type') !== 'shop_order') {
            return;
        }
        if (!empty($_GET['gdb_meta_key']) && !empty($_GET['gdb_meta_value'])) {
            $query->set('meta_key', sanitize_key($_GET['gdb_meta_key']));
            $query->set('meta_value', sanitize_text_field($_GET['gdb_meta_value']));
        }
        if (!empty($_GET['gdb_statuses'])) {
            $statuses = array_map('sanitize_key', explode(',', sanitize_text_field($_GET['gdb_statuses'])));
            $query->set('post_status', $statuses);
        }
    }



    public function filter_hpos_orders_by_gdb_params($query_args) {
        if (!is_admin()) {
            return $query_args;
        }
        if (!empty($_GET['gdb_meta_key']) && !empty($_GET['gdb_meta_value'])) {
            $query_args['meta_key'] = sanitize_key($_GET['gdb_meta_key']);
            $query_args['meta_value'] = sanitize_text_field($_GET['gdb_meta_value']);
        }
        if (!empty($_GET['gdb_statuses'])) {
            $query_args['status'] = array_map('sanitize_key', explode(',', sanitize_text_field($_GET['gdb_statuses'])));
        }
        return $query_args;
    }

    public function add_menu() {
        add_menu_page(
            __('گلدن داشبورد', 'golden-dashboard'),
            __('گلدن داشبورد', 'golden-dashboard'),
            'manage_options',
            'gdb-dashboard',
            [$this, 'render_dashboard_page'],
            'dashicons-awards',
            30
        );

        add_submenu_page(
            'gdb-dashboard',
            __('نمای کلی', 'golden-dashboard'),
            __('نمای کلی', 'golden-dashboard'),
            'manage_options',
            'gdb-dashboard',
            [$this, 'render_dashboard_page']
        );

        add_submenu_page(
            'gdb-dashboard',
            __('برداشت کیف پول', 'golden-dashboard'),
            __('برداشت کیف پول', 'golden-dashboard'),
            'manage_options',
            'gdb-withdraw-requests',
            [$this, 'render_withdraw_requests_page']
        );

        add_submenu_page(
            'gdb-dashboard',
            __('تاریخچه تراکنش‌ها', 'golden-dashboard'),
            __('تاریخچه تراکنش‌ها', 'golden-dashboard'),
            'manage_options',
            'gdb-transactions-history',
            [$this, 'render_transactions_history_page']
        );

        add_submenu_page(
            'gdb-dashboard',
            __('شارژ دستی کیف پول', 'golden-dashboard'),
            __('شارژ دستی', 'golden-dashboard'),
            'manage_options',
            'gdb-manual-credit',
            [$this, 'render_manual_credit_page']
        );

        add_submenu_page(
            'gdb-dashboard',
            __('گزارش کارمزد و درآمد', 'golden-dashboard'),
            __('گزارش کارمزد و درآمد', 'golden-dashboard'),
            'manage_options',
            'gdb-fee-report',
            [$this, 'render_fee_report_page']
        );

        add_submenu_page(
            'gdb-dashboard',
            __('تنظیمات', 'golden-dashboard'),
            __('تنظیمات', 'golden-dashboard'),
            'manage_options',
            'gdb-settings',
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            null,
            __('جزئیات تراکنش', 'golden-dashboard'),
            __('جزئیات تراکنش', 'golden-dashboard'),
            'manage_options',
            'gdb-transaction-detail',
            [$this, 'render_transaction_detail_page']
        );

        add_submenu_page(
            null,
            __('لاگ امنیتی', 'golden-dashboard'),
            __('لاگ امنیتی', 'golden-dashboard'),
            'manage_options',
            'gdb-security-log',
            [$this, 'render_security_log_page']
        );
    }

    public function register_settings() {
        register_setting('gdb_settings_group', 'gdb_enable_admin_email');
        register_setting('gdb_settings_group', 'gdb_enable_user_email');
        register_setting('gdb_settings_group', 'gdb_withdraw_fee_percent');
    }

    

    public function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'golden-dashboard'));
        }
        include GDB_ADMIN_PATH . 'admin-dashboard.php';
    }

    public function render_withdraw_requests_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'golden-dashboard'));
        }
        include GDB_ADMIN_PATH . 'admin-withdraw-requests.php';
    }

    public function render_transactions_history_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'golden-dashboard'));
        }
        include GDB_ADMIN_PATH . 'admin-transaction-history.php';
    }

    public function render_fee_report_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'golden-dashboard'));
        }
        include GDB_ADMIN_PATH . 'admin-fee-report.php';
    }

    public function render_transaction_detail_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'golden-dashboard'));
        }
        include GDB_ADMIN_PATH . 'admin-transaction-detail.php';
    }

    public function render_manual_credit_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'golden-dashboard'));
        }
        include GDB_ADMIN_PATH . 'admin-manual-credit.php';
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'golden-dashboard'));
        }
        include GDB_ADMIN_PATH . 'admin-settings.php';
    }

    public function render_security_log_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'golden-dashboard'));
        }
        include GDB_ADMIN_PATH . 'admin-security-log.php';
    }

    

    private function get_filters_from_request($src) {
        $status   = isset($src['status']) ? sanitize_text_field(wp_unslash($src['status'])) : '';
        $search   = isset($src['search']) ? sanitize_text_field(wp_unslash($src['search'])) : '';
        $tracking = isset($src['tracking']) ? sanitize_text_field(wp_unslash($src['tracking'])) : '';

        $query = [];
        if ($status)        $query['status']  = $status;
        if ($search !== '') $query['search']  = $search;
        if ($tracking !== '') $query['tracking'] = $tracking;

        return [
            'query' => $query,
            'display' => [
                'status'   => $status,
                'search'   => $search,
                'tracking' => $tracking,
            ],
        ];
    }

    public function handle_ajax_filter() {
        check_ajax_referer('gdb_filter_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('دسترسی غیرمجاز', 'golden-dashboard')], 403);
        }

        $filters = $this->get_filters_from_request($_POST);
        $per_page = 20;
        $page = isset($_POST['paged']) ? max(1, absint($_POST['paged'])) : 1;

        $data = GDB_Withdraw_Request::get_requests($filters['query'], $per_page, $page);
        
        ob_start();
        if ($data['items']) {
            $this->render_requests_table($data['items'], $data['total'], $data['pages'], $page);
        } else {
            echo '<p>' . __('هیچ درخواستی یافت نشد.', 'golden-dashboard') . '</p>';
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'html'  => $html,
            'total' => $data['total'],
        ]);
    }

    public function render_requests_table($requests, $total, $pages, $page) {
        if ($requests) :
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('شناسه', 'golden-dashboard'); ?></th>
                        <th><?php _e('کاربر', 'golden-dashboard'); ?></th>
                        <th><?php _e('مبلغ درخواستی', 'golden-dashboard'); ?></th>
                        <th><?php _e('کارمزد', 'golden-dashboard'); ?></th>
                        <th><?php _e('مبلغ قابل واریز', 'golden-dashboard'); ?></th>
                        <th><?php _e('کد پیگیری', 'golden-dashboard'); ?></th>
                        <th><?php _e('وضعیت', 'golden-dashboard'); ?></th>
                        <th><?php _e('تاریخ درخواست', 'golden-dashboard'); ?></th>
                        <th><?php _e('عملیات', 'golden-dashboard'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req) :
                        $user = get_userdata($req->user_id);
                        $status_meta = $this->get_status_meta($req->status);
                        $fee_amount = isset($req->fee_amount) ? $req->fee_amount : 0;
                        $net_amount = isset($req->net_amount) ? $req->net_amount : $req->amount;
                        ?>
                        <tr>
                            <td><?php echo $req->id; ?></td>
                            <td><?php echo $user ? $user->display_name . ' (#' . $user->ID . ')' : __('نامشخص', 'golden-dashboard'); ?></td>
                            <td><?php echo gdb_price($req->amount); ?></td>
                            <td><?php echo $fee_amount > 0 ? gdb_price($fee_amount) : '-'; ?></td>
                            <td><strong><?php echo gdb_price($net_amount); ?></strong></td>
                            <td><code><?php echo esc_html($req->tracking_code); ?></code></td>
                            <td><span class="gdb-status-badge gdb-status-<?php echo esc_attr($status_meta[0]); ?>"><?php echo esc_html($status_meta[1]); ?></span></td>
                            <td><?php echo esc_html(gdb_date_jalali($req->created_at, true)); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=gdb-transaction-detail&id=' . $req->id); ?>" class="button button-primary button-small">
                                    <?php _e('مشاهده و مدیریت', 'golden-dashboard'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $base_url = admin_url('admin.php?page=gdb-withdraw-requests');
                    echo paginate_links([
                        'base'    => add_query_arg('paged', '%#%', $base_url),
                        'format'  => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total'   => $pages,
                        'current' => $page,
                    ]);
                    ?>
                </div>
            </div>
        <?php else : ?>
            <p><?php _e('هیچ درخواستی یافت نشد.', 'golden-dashboard'); ?></p>
        <?php endif;
    }

    private function get_status_meta($status) {
        $map = [
            'pending'    => ['pending', __('در حال بررسی', 'golden-dashboard')],
            'completed'  => ['completed', __('تایید شده', 'golden-dashboard')],
            'rejected'   => ['rejected', __('رد شده', 'golden-dashboard')],
            'processing' => ['processing', __('در حال پردازش', 'golden-dashboard')],
            'on-hold'    => ['on-hold', __('در انتظار', 'golden-dashboard')],
            'cancelled'  => ['cancelled', __('لغو شده', 'golden-dashboard')],
            'refunded'   => ['refunded', __('بازگشت وجه', 'golden-dashboard')],
            'failed'     => ['failed', __('ناموفق', 'golden-dashboard')],
        ];
        return isset($map[$status]) ? $map[$status] : ['default', $status];
    }

    public function handle_approve() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'golden-dashboard'));
        }
        check_admin_referer('gdb_approve_withdraw', 'gdb_approve_nonce');

        $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
        $referer = wp_get_referer() ?: admin_url('admin.php?page=gdb-withdraw-requests');

        if (!$request_id) {
            wp_redirect(add_query_arg('gdb_error', rawurlencode(__('شناسه درخواست نامعتبر است.', 'golden-dashboard')), $referer));
            exit;
        }

        $payment_data = [
            'bank_transaction_id' => sanitize_text_field($_POST['bank_transaction_id'] ?? ''),
            'admin_note'          => sanitize_text_field($_POST['admin_note'] ?? ''),
            'bank_date'           => gdb_normalize_admin_date_input(sanitize_text_field($_POST['bank_date'] ?? '')),
        ];

        $result = GDB_Withdraw_Request::approve($request_id, $payment_data);
        if (is_wp_error($result)) {
            wp_redirect(add_query_arg('gdb_error', rawurlencode($result->get_error_message()), $referer));
            exit;
        }

        $this->save_audit_log($request_id, 'approved', get_current_user_id(), $payment_data['admin_note']);
        wp_redirect(add_query_arg('message', 'approved', $referer));
        exit;
    }

    public function handle_reject() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'golden-dashboard'));
        }
        check_admin_referer('gdb_reject_withdraw', 'gdb_reject_nonce');

        $request_id = isset($_POST['request_id']) ? absint($_POST['request_id']) : 0;
        $referer = wp_get_referer() ?: admin_url('admin.php?page=gdb-withdraw-requests');

        if (!$request_id) {
            wp_redirect(add_query_arg('gdb_error', rawurlencode(__('شناسه درخواست نامعتبر است.', 'golden-dashboard')), $referer));
            exit;
        }

        $reason = sanitize_text_field($_POST['reason'] ?? '');
        $result = GDB_Withdraw_Request::reject($request_id, $reason);
        if (is_wp_error($result)) {
            wp_redirect(add_query_arg('gdb_error', rawurlencode($result->get_error_message()), $referer));
            exit;
        }

        $this->save_audit_log($request_id, 'rejected', get_current_user_id(), $reason);
        wp_redirect(add_query_arg('message', 'rejected', $referer));
        exit;
    }

    public function handle_update_transaction() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'golden-dashboard'));
        }
        check_admin_referer('gdb_update_transaction', 'gdb_update_nonce');

        $transaction_id = isset($_POST['transaction_id']) ? absint($_POST['transaction_id']) : 0;
        if (!$transaction_id) {
            gdb_admin_redirect_error(__('شناسه تراکنش نامعتبر است.', 'golden-dashboard'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_transactions';

        $update_data = [
            'bank_transaction_id' => sanitize_text_field($_POST['bank_transaction_id'] ?? ''),
            'admin_note'          => sanitize_textarea_field($_POST['admin_note'] ?? ''),
            'bank_date'           => gdb_normalize_admin_date_input(sanitize_text_field($_POST['bank_date'] ?? '')),
            'updated_at'          => current_time('mysql'),
        ];

        $wpdb->update(
            $table,
            $update_data,
            ['id' => $transaction_id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );

        $this->save_audit_log($transaction_id, 'updated', get_current_user_id(), sprintf(
            __('به‌روزرسانی: شماره تراکنش=%s، تاریخ واریز=%s، یادداشت=%s', 'golden-dashboard'),
            $update_data['bank_transaction_id'],
            $update_data['bank_date'] ? gdb_date_jalali($update_data['bank_date'], false) : '',
            $update_data['admin_note']
        ));

        wp_redirect(add_query_arg('message', 'updated', wp_get_referer()));
        exit;
    }

    public function export_transactions_csv() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'golden-dashboard'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_transactions';

        $user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

        $where = ['1=1'];
        $params = [];

        if ($user_id) {
            $where[] = 'user_id = %d';
            $params[] = $user_id;
        }
        if ($type && in_array($type, ['credit', 'debit'])) {
            $where[] = 'type = %s';
            $params[] = $type;
        }
        if ($status) {
            $where[] = 'status = %s';
            $params[] = $status;
        }
        if ($date_from) {
            $where[] = 'DATE(created_at) >= %s';
            $params[] = $date_from;
        }
        if ($date_to) {
            $where[] = 'DATE(created_at) <= %s';
            $params[] = $date_to;
        }
        if ($search) {
            $where[] = '(description LIKE %s OR tracking_code LIKE %s OR transaction_type LIKE %s OR user_id IN (SELECT ID FROM ' . $wpdb->users . ' WHERE display_name LIKE %s OR user_login LIKE %s OR user_email LIKE %s))';
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $params = array_merge($params, [$search_like, $search_like, $search_like, $search_like, $search_like, $search_like]);
        }

        $where_sql = implode(' AND ', $where);
        $sql = $wpdb->prepare("SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC", $params);
        $transactions = $wpdb->get_results($sql);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="transactions-' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, [
            __('شناسه', 'golden-dashboard'),
            __('کاربر', 'golden-dashboard'),
            __('نوع', 'golden-dashboard'),
            __('مبلغ', 'golden-dashboard'),
            __('کارمزد', 'golden-dashboard'),
            __('مبلغ خالص', 'golden-dashboard'),
            __('وضعیت', 'golden-dashboard'),
            __('کد پیگیری', 'golden-dashboard'),
            __('تاریخ ایجاد', 'golden-dashboard'),
            __('توضیحات', 'golden-dashboard'),
        ]);

        foreach ($transactions as $tx) {
            $user = get_userdata($tx->user_id);
            fputcsv($output, [
                $tx->id,
                $user ? $user->display_name : __('نامشخص', 'golden-dashboard'),
                $tx->type === 'credit' ? __('واریز', 'golden-dashboard') : __('برداشت', 'golden-dashboard'),
                gdb_price_plain($tx->amount),
                isset($tx->fee_amount) ? gdb_price_plain($tx->fee_amount) : '',
                isset($tx->net_amount) ? gdb_price_plain($tx->net_amount) : gdb_price_plain($tx->amount),
                $tx->status,
                $tx->tracking_code,
                gdb_date_jalali($tx->created_at, true),
                $tx->description,
            ]);
        }

        fclose($output);
        exit;
    }

    public function handle_save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'golden-dashboard'));
        }
        check_admin_referer('gdb_save_settings', 'gdb_settings_nonce');

        
        
        
        $tab = sanitize_key($_POST['gdb_settings_tab'] ?? 'general');

        if ($tab === 'wallet') {
            update_option('gdb_withdraw_fee_percent', floatval($_POST['gdb_withdraw_fee_percent'] ?? 0));
            if (isset($_POST['gdb_wallet_topup_product_id'])) {
                update_option('gdb_wallet_topup_product_id', absint($_POST['gdb_wallet_topup_product_id']));
            }
        } else {
            update_option('gdb_enable_admin_email', sanitize_text_field($_POST['gdb_enable_admin_email'] ?? 'yes'));
            update_option('gdb_enable_user_email', sanitize_text_field($_POST['gdb_enable_user_email'] ?? 'yes'));
            update_option('gdb_uninstall_delete_tables', isset($_POST['gdb_uninstall_delete_tables']) ? true : false);
        }

        $redirect = add_query_arg([
            'page'        => 'gdb-settings',
            'tab'         => $tab,
            'gdb_message' => rawurlencode(__('تنظیمات با موفقیت ذخیره شد.', 'golden-dashboard')),
        ], admin_url('admin.php'));
        wp_redirect($redirect);
        exit;
    }

    public function handle_save_security_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'golden-dashboard'));
        }
        check_admin_referer('gdb_save_security_settings', 'gdb_security_nonce');

        update_option('gdb_security_rate_limit_enabled', isset($_POST['rate_limit_enabled']) ? '1' : '');
        update_option('gdb_security_rate_limit_window', max(1, absint($_POST['rate_limit_window'] ?? 60)));
        update_option('gdb_security_rate_limit_max_attempts', max(1, absint($_POST['rate_limit_max_attempts'] ?? 5)));
        update_option('gdb_security_rate_limit_block_duration', max(1, absint($_POST['rate_limit_block_duration'] ?? 300)));
        update_option('gdb_security_suspicious_amount_threshold', max(0, floatval($_POST['suspicious_amount_threshold'] ?? 0)));
        update_option('gdb_security_require_verification_above', max(0, floatval($_POST['require_verification_above'] ?? 0)));
        update_option('gdb_security_alert_admin_on_suspicious', isset($_POST['alert_admin_on_suspicious']) ? '1' : '');
        update_option('gdb_security_max_daily_transactions', max(0, absint($_POST['max_daily_transactions'] ?? 0)));
        update_option('gdb_security_max_transaction_amount', max(0, floatval($_POST['max_transaction_amount'] ?? 0)));
        update_option('gdb_security_log_all_transactions', isset($_POST['log_all_transactions']) ? '1' : '');

        $redirect = add_query_arg([
            'page'        => 'gdb-settings',
            'tab'         => 'security',
            'gdb_message' => rawurlencode(__('تنظیمات امنیتی با موفقیت ذخیره شد.', 'golden-dashboard')),
        ], admin_url('admin.php'));
        wp_redirect($redirect);
        exit;
    }

    public function handle_save_gold_type() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'golden-dashboard'));
        }
        check_admin_referer('gdb_save_gold_type', 'gdb_gold_type_nonce');

        if (!class_exists('GDB_Gold_Wallet')) {
            wp_die(__('سیستم کیف پول طلا در دسترس نیست.', 'golden-dashboard'));
        }

        $id = absint($_POST['id'] ?? 0);
        GDB_Gold_Wallet::save_type($_POST, $id);

        $redirect = add_query_arg([
            'page'        => 'gdb-settings',
            'tab'         => 'gold-wallet',
            'gdb_message' => rawurlencode(__('نوع کیف پول طلا ذخیره شد.', 'golden-dashboard')),
        ], admin_url('admin.php'));
        wp_redirect($redirect);
        exit;
    }

    public function handle_delete_gold_type() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'golden-dashboard'));
        }
        check_admin_referer('gdb_delete_gold_type', 'gdb_delete_gold_type_nonce');

        if (class_exists('GDB_Gold_Wallet')) {
            GDB_Gold_Wallet::delete_type(absint($_POST['id'] ?? 0));
        }

        $redirect = add_query_arg([
            'page'        => 'gdb-settings',
            'tab'         => 'gold-wallet',
            'gdb_message' => rawurlencode(__('نوع کیف پول طلا حذف شد.', 'golden-dashboard')),
        ], admin_url('admin.php'));
        wp_redirect($redirect);
        exit;
    }

    public function handle_save_cashback_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'golden-dashboard'));
        }
        check_admin_referer('gdb_save_cashback_settings', 'gdb_cashback_nonce');

        update_option('gdb_cashback_topup_enabled', isset($_POST['cashback_topup_enabled']) ? '1' : '');
        update_option('gdb_cashback_topup_percent', max(0, min(100, floatval($_POST['cashback_topup_percent'] ?? 0))));

        update_option('gdb_cashback_gold_enabled', isset($_POST['cashback_gold_enabled']) ? '1' : '');
        update_option('gdb_cashback_gold_percent', max(0, min(100, floatval($_POST['cashback_gold_percent'] ?? 0))));

        update_option('gdb_cashback_order_enabled', isset($_POST['cashback_order_enabled']) ? '1' : '');
        update_option('gdb_cashback_order_percent', max(0, min(100, floatval($_POST['cashback_order_percent'] ?? 0))));

        $redirect = add_query_arg([
            'page'        => 'gdb-settings',
            'tab'         => 'cashback',
            'gdb_message' => rawurlencode(__('تنظیمات کش‌بک با موفقیت ذخیره شد.', 'golden-dashboard')),
        ], admin_url('admin.php'));
        wp_redirect($redirect);
        exit;
    }

    public function handle_block_ip() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'golden-dashboard'));
        }
        check_admin_referer('gdb_block_ip', 'gdb_block_ip_nonce');

        $ip = sanitize_text_field($_POST['ip_address'] ?? '');
        $reason = sanitize_text_field($_POST['reason'] ?? '');

        if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
            GDB_Security::block_ip($ip, $reason, get_current_user_id());
        }

        $redirect = add_query_arg([
            'page'        => 'gdb-settings',
            'tab'         => 'security',
            'gdb_message' => rawurlencode(__('آی‌پی مسدود شد.', 'golden-dashboard')),
        ], admin_url('admin.php'));
        wp_redirect($redirect);
        exit;
    }

    public function handle_unblock_ip() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'golden-dashboard'));
        }
        check_admin_referer('gdb_unblock_ip', 'gdb_unblock_ip_nonce');

        GDB_Security::unblock_ip(absint($_POST['id'] ?? 0));

        $redirect = add_query_arg([
            'page'        => 'gdb-settings',
            'tab'         => 'security',
            'gdb_message' => rawurlencode(__('رفع مسدودی انجام شد.', 'golden-dashboard')),
        ], admin_url('admin.php'));
        wp_redirect($redirect);
        exit;
    }

    

    public function handle_manual_credit() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'golden-dashboard'));
        }
        check_admin_referer('gdb_manual_credit_action', 'gdb_manual_credit_nonce');

        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $type = isset($_POST['transaction_type']) ? sanitize_text_field($_POST['transaction_type']) : '';
        
        
        
        
        
        
        
        
        $amount = isset($_POST['amount']) ? gdb_storage_amount(floatval($_POST['amount'])) : 0;
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

        if (!$user_id || !get_userdata($user_id)) {
            gdb_admin_redirect_error(__('کاربر نامعتبر است.', 'golden-dashboard'));
        }
        if (!in_array($type, ['credit', 'debit'])) {
            gdb_admin_redirect_error(__('نوع تراکنش نامعتبر است.', 'golden-dashboard'));
        }
        if ($amount <= 0) {
            gdb_admin_redirect_error(__('مبلغ باید بزرگتر از صفر باشد.', 'golden-dashboard'));
        }

        
        $fee_amount = 0;
        $net_amount = $amount;
        if ($type === 'debit') {
            $fee_type = isset($_POST['fee_type']) ? sanitize_text_field($_POST['fee_type']) : 'percent';
            $fee_value = isset($_POST['fee_value']) ? floatval($_POST['fee_value']) : 0;
            if ($fee_value > 0) {
                if ($fee_type === 'fixed') {
                    $fee_amount = gdb_storage_amount($fee_value);
                } else {
                    $fee_amount = $amount * (min($fee_value, 100) / 100);
                }
                $fee_amount = min($fee_amount, $amount); 
                $net_amount = $amount - $fee_amount;
            }
        }

        
        
        $created_at = current_time('mysql');
        if (!empty($_POST['transaction_date'])) {
            $tx_date = gdb_normalize_admin_date_input(sanitize_text_field($_POST['transaction_date']));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tx_date)) {
                $created_at = $tx_date . ' ' . date('H:i:s', current_time('timestamp'));
            }
        }

        $result = self::process_manual_transaction($user_id, $type, $amount, $description, [
            'fee_amount'  => $fee_amount,
            'net_amount'  => $net_amount,
            'created_at'  => $created_at,
        ]);
        if (is_wp_error($result)) {
            gdb_admin_redirect_error($result->get_error_message());
        }

        $return_url = !empty($_POST['gdb_return_url']) ? esc_url_raw(wp_unslash($_POST['gdb_return_url'])) : wp_get_referer();
        wp_redirect(add_query_arg(['message' => 'success', 'user_id' => $user_id], $return_url ?: admin_url()));
        exit;
    }



    public function handle_delete_manual_transaction() {
        if (!current_user_can('manage_options')) {
            wp_die(__('دسترسی غیرمجاز', 'golden-dashboard'));
        }
        check_admin_referer('gdb_delete_manual_transaction_action', 'gdb_delete_manual_transaction_nonce');

        global $wpdb;
        $table_transactions = $wpdb->prefix . 'gd_wallet_transactions';
        $table_wallet = $wpdb->prefix . 'gd_user_wallet';

        $transaction_id = isset($_POST['transaction_id']) ? absint($_POST['transaction_id']) : 0;
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;

        $tx = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_transactions} WHERE id = %d AND user_id = %d", $transaction_id, $user_id));
        if (!$tx || !in_array($tx->transaction_type, ['admin_credit', 'admin_debit'])) {
            gdb_admin_redirect_error(__('این تراکنش قابل حذف نیست.', 'golden-dashboard'));
        }

        
        
        $reverse_effect = ($tx->type === 'credit') ? -1 * (float) $tx->amount : (float) $tx->amount;

        $wpdb->query('START TRANSACTION');
        try {
            $wallet = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_wallet} WHERE user_id = %d FOR UPDATE", $user_id));
            if (!$wallet) {
                throw new Exception(__('کیف پول این کاربر یافت نشد.', 'golden-dashboard'));
            }

            $new_balance = $wallet->balance + $reverse_effect;
            if ($new_balance < 0) {
                throw new Exception(__('حذف این تراکنش باعث منفی‌شدنِ موجودی کاربر می‌شود.', 'golden-dashboard'));
            }

            $new_total_credit = $wallet->total_credit - (($tx->type === 'credit') ? $tx->amount : 0);
            $new_total_debit = $wallet->total_debit - (($tx->type === 'debit') ? $tx->amount : 0);

            $wpdb->update(
                $table_wallet,
                ['balance' => $new_balance, 'total_credit' => max(0, $new_total_credit), 'total_debit' => max(0, $new_total_debit), 'updated_at' => current_time('mysql')],
                ['user_id' => $user_id],
                ['%f', '%f', '%f', '%s'],
                ['%d']
            );

            $wpdb->delete($table_transactions, ['id' => $transaction_id], ['%d']);

            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            gdb_admin_redirect_error($e->getMessage());
        }

        $this->save_audit_log(0, 'deleted', get_current_user_id(), sprintf(
            __('حذف تراکنش دستیِ شماره %d (مبلغ: %s، کاربر: %d) توسط مدیر', 'golden-dashboard'),
            $transaction_id,
            gdb_price_plain($tx->amount),
            $user_id
        ));

        $return_url = !empty($_POST['gdb_return_url']) ? esc_url_raw(wp_unslash($_POST['gdb_return_url'])) : wp_get_referer();
        wp_redirect(add_query_arg(['message' => 'updated', 'user_id' => $user_id], remove_query_arg(['edit_tx', 'gdb_error'], $return_url ?: admin_url())));
        exit;
    }

    public function ajax_search_users() {
        check_ajax_referer('gdb_search_users_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز');
        }

        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        if (strlen($term) < 2) {
            wp_send_json_success([]);
        }

        $users = get_users([
            'search' => '*' . $term . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'number' => 20,
            'fields' => ['ID', 'display_name', 'user_email'],
        ]);

        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->ID,
                'display_name' => $user->display_name,
                'user_email' => $user->user_email,
                'text' => $user->display_name . ' (' . $user->user_email . ')',
            ];
        }

        wp_send_json_success($results);
    }

    public static function process_manual_transaction($user_id, $type, $amount, $description = '', $extra = []) {
        global $wpdb;
        $user_id = absint($user_id);
        $amount = floatval($amount);
        $type = $type === 'credit' ? 'credit' : 'debit';

        
        
        
        
        $fee_amount = ($type === 'debit' && !empty($extra['fee_amount'])) ? min(floatval($extra['fee_amount']), $amount) : 0;
        $net_amount = $amount - $fee_amount;
        $created_at = !empty($extra['created_at']) ? $extra['created_at'] : current_time('mysql');

        if ($user_id <= 0 || $amount <= 0) {
            return new WP_Error('invalid_data', __('داده‌های ورودی نامعتبر است.', 'golden-dashboard'));
        }

        $balance_before = GDB_Wallet::balance($user_id);
        if ($type === 'debit' && $amount > $balance_before) {
            return new WP_Error('insufficient_balance', __('موجودی کاربر برای برداشت کافی نیست.', 'golden-dashboard'));
        }

        $balance_after = ($type === 'credit') ? $balance_before + $amount : $balance_before - $amount;

        $table_transactions = $wpdb->prefix . 'gd_wallet_transactions';
        $table_wallet = $wpdb->prefix . 'gd_user_wallet';

        $wpdb->query('START TRANSACTION');

        try {
            $wallet = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_wallet} WHERE user_id = %d FOR UPDATE", $user_id));
            if (!$wallet) {
                $wpdb->insert(
                    $table_wallet,
                    [
                        'user_id' => $user_id,
                        'balance' => $balance_after,
                        'total_credit' => ($type === 'credit') ? $amount : 0,
                        'total_debit' => ($type === 'debit') ? $amount : 0,
                        'created_at' => current_time('mysql'),
                        'updated_at' => current_time('mysql'),
                    ],
                    ['%d', '%f', '%f', '%f', '%s', '%s']
                );
                if ($wpdb->last_error) {
                    throw new Exception($wpdb->last_error);
                }
            } else {
                $new_balance = ($type === 'credit') ? $wallet->balance + $amount : $wallet->balance - $amount;
                $new_total_credit = ($type === 'credit') ? $wallet->total_credit + $amount : $wallet->total_credit;
                $new_total_debit = ($type === 'debit') ? $wallet->total_debit + $amount : $wallet->total_debit;
                $wpdb->update(
                    $table_wallet,
                    [
                        'balance' => $new_balance,
                        'total_credit' => $new_total_credit,
                        'total_debit' => $new_total_debit,
                        'updated_at' => current_time('mysql'),
                    ],
                    ['user_id' => $user_id],
                    ['%f', '%f', '%f', '%s'],
                    ['%d']
                );
                if ($wpdb->last_error) {
                    throw new Exception($wpdb->last_error);
                }
            }

            $tracking_code = function_exists('gdb_generate_tracking_code') ? gdb_generate_tracking_code() : rand(100000000000, 999999999999);
            $transaction_type = ($type === 'credit') ? 'admin_credit' : 'admin_debit';
            $desc = $description ?: (($type === 'credit') ? __('شارژ دستی توسط مدیر', 'golden-dashboard') : __('برداشت دستی توسط مدیر', 'golden-dashboard'));
            if ($fee_amount > 0) {
                $desc .= ' - ' . sprintf(
                    __('کارمزد: %s - مبلغ قابل واریز: %s', 'golden-dashboard'),
                    gdb_price_plain($fee_amount),
                    gdb_price_plain($net_amount)
                );
            }
            $desc .= ' - ' . sprintf(__('کد پیگیری: %s', 'golden-dashboard'), $tracking_code);

            $wpdb->insert(
                $table_transactions,
                [
                    'user_id' => $user_id,
                    'type' => $type,
                    'amount' => $amount,
                    'fee_amount' => $fee_amount,
                    'net_amount' => $net_amount,
                    'balance_before' => $balance_before,
                    'balance_after' => $balance_after,
                    'transaction_type' => $transaction_type,
                    'reference_id' => 0,
                    'description' => $desc,
                    'status' => 'completed',
                    'tracking_code' => $tracking_code,
                    'created_by' => get_current_user_id(),
                    'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
                    'user_agent' => substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                    'created_at' => $created_at,
                    'updated_at' => current_time('mysql'),
                ],
                [
                    '%d', '%s', '%f', '%f', '%f', '%f', '%f', '%s', '%d', '%s',
                    '%s', '%s', '%d', '%s', '%s', '%s', '%s'
                ]
            );
            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            $wpdb->query('COMMIT');

            $audit_log_table = $wpdb->prefix . 'gd_audit_log';
            $wpdb->insert(
                $audit_log_table,
                [
                    'transaction_id' => $wpdb->insert_id,
                    'user_id' => get_current_user_id(),
                    'action' => 'manual_' . $type,
                    'note' => $description,
                    'created_at' => current_time('mysql'),
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );

            return [
                'new_balance' => $balance_after,
                'transaction_id' => $wpdb->insert_id,
            ];

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('transaction_failed', $e->getMessage());
        }
    }

    

    private function save_audit_log($transaction_id, $action, $user_id, $note = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_audit_log';
        $wpdb->insert(
            $table,
            [
                'transaction_id' => $transaction_id,
                'user_id' => $user_id,
                'action' => $action,
                'note' => $note,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );
    }

    private function get_audit_log($transaction_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_audit_log';
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE transaction_id = %d ORDER BY id DESC", $transaction_id)
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'gdb-') === false) {
            return;
        }

        wp_enqueue_style(
            'gdb-admin',
            GDB_URL . 'assets/css/admin.css',
            [],
            GDB_VERSION
        );

        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        $parsidate_active = defined('WP_PARSI_URL') && defined('WP_PARSI_VER');

        if ($parsidate_active) {
            $debug_suffix = (defined('WP_PARSI_DEBUG_MODE') && WP_PARSI_DEBUG_MODE) ? '' : '.min';
            wp_enqueue_script(
                'gdb-parsidate-datepicker',
                WP_PARSI_URL . 'assets/js-admin/jalalidatepicker.min.js',
                [],
                WP_PARSI_VER,
                true
            );
            wp_enqueue_style(
                'gdb-parsidate-datepicker',
                WP_PARSI_URL . 'assets/css-admin/jalalidatepicker' . $debug_suffix . '.css',
                [],
                WP_PARSI_VER
            );
        } else {
            wp_enqueue_script('jquery-ui-datepicker');
        }

        if (strpos($hook, 'gdb-manual-credit') !== false || strpos($hook, 'gdb-fee-report') !== false) {
            wp_enqueue_script('select2');
            wp_enqueue_style('select2');
        }

        wp_enqueue_script(
            'gdb-admin',
            GDB_URL . 'assets/js/admin.js',
            $parsidate_active ? ['jquery'] : ['jquery', 'jquery-ui-datepicker'],
            GDB_VERSION,
            true
        );

        wp_localize_script('gdb-admin', 'gdbAdminVars', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('gdb_filter_nonce'),
            
            
            
            'parsidateActive' => $parsidate_active,
        ]);
    }
}
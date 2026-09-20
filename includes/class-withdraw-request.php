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

class GDB_Withdraw_Request {

    public static function create($user_id, $amount, $meta_data = [], $min_amount = null, $max_amount = null) {
        global $wpdb;

        $user_id = absint($user_id);
        $amount = floatval($amount); 

        if ($user_id <= 0 || $amount <= 0) {
            return new WP_Error('invalid_input', __('مبلغ یا کاربر نامعتبر است.', 'golden-dashboard'));
        }

        
        
        
        $min_amount = ($min_amount !== null && $min_amount > 0) ? (float) $min_amount : (float) get_option('gdb_withdraw_min_amount', 1000);
        $max_amount = ($max_amount !== null && $max_amount > 0) ? (float) $max_amount : (float) get_option('gdb_withdraw_max_amount', 50000000);
        $fee_percent = (float) get_option('gdb_withdraw_fee_percent', 0);

        if ($amount < $min_amount) {
            /* translators: %s: minimum withdrawal amount */
            return new WP_Error('min_amount', sprintf(__('حداقل مبلغ برداشت %s است.', 'golden-dashboard'), gdb_price_plain($min_amount)));
        }

        if ($amount > $max_amount) {
            /* translators: %s: maximum withdrawal amount */
            return new WP_Error('max_amount', sprintf(__('حداکثر مبلغ برداشت %s است.', 'golden-dashboard'), gdb_price_plain($max_amount)));
        }

        $balance = GDB_Wallet::balance($user_id);
        if ($amount > $balance) {
            return new WP_Error('insufficient_balance', __('موجودی کافی نیست.', 'golden-dashboard'));
        }

        
        $fee_amount = $amount * ($fee_percent / 100);
        $net_amount = $amount - $fee_amount;

        $tracking_code = self::generate_tracking_code();
        $table = $wpdb->prefix . 'gd_wallet_transactions';
        $wallet_table = $wpdb->prefix . 'gd_user_wallet';

        $meta_data = array_merge($meta_data, [
            'fee_percent' => $fee_percent,
            'fee_amount' => $fee_amount,
            'net_amount' => $net_amount,
            'original_amount' => $amount,
        ]);

        
        
        
        
        $wpdb->query('START TRANSACTION');
        try {
            $wallet = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wallet_table} WHERE user_id = %d FOR UPDATE", $user_id));
            if (!$wallet) {
                throw new Exception(__('کیف پول کاربر یافت نشد.', 'golden-dashboard'));
            }
            $balance_before = (float) $wallet->balance;
            if ($amount > $balance_before) {
                throw new Exception(__('موجودی کافی نیست.', 'golden-dashboard'));
            }
            $balance_after = $balance_before - $amount;

            $wpdb->update(
                $wallet_table,
                ['balance' => $balance_after, 'total_debit' => (float) $wallet->total_debit + $amount, 'updated_at' => current_time('mysql')],
                ['user_id' => $user_id],
                ['%f', '%f', '%s'],
                ['%d']
            );

            $result = $wpdb->insert(
                $table,
                [
                    'user_id'          => $user_id,
                    'type'             => 'debit',
                    'amount'           => $amount,
                    'fee_amount'       => $fee_amount,
                    'net_amount'       => $net_amount,
                    'balance_before'   => $balance_before,
                    'balance_after'    => $balance_after,
                    'transaction_type' => 'withdraw_request',
                    'reference_id'     => 0,
                    'description'      => sprintf(
                        /* translators: 1: amount, 2: fee amount, 3: net amount, 4: tracking code */
                        __('درخواست برداشت به مبلغ %1$s (کارمزد: %2$s - مبلغ قابل واریز: %3$s) - کد پیگیری: %4$s', 'golden-dashboard'),
                        gdb_price_plain($amount),
                        gdb_price_plain($fee_amount),
                        gdb_price_plain($net_amount),
                        $tracking_code
                    ),
                    'status'           => 'pending',
                    'tracking_code'    => $tracking_code,
                    'admin_note'       => '',
                    'bank_transaction_id' => '',
                    'bank_date'        => '',
                    'meta_data'        => maybe_serialize($meta_data),
                    'created_by'       => $user_id,
                    'ip_address'       => sanitize_text_field((isset($_SERVER['REMOTE_ADDR']) ? wp_unslash($_SERVER['REMOTE_ADDR']) : '')),
                    'user_agent'       => substr(sanitize_text_field((isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '')), 0, 255),
                    'session_id'       => function_exists('session_id') && session_status() === PHP_SESSION_ACTIVE ? session_id() : '',
                    'is_suspicious'    => 0,
                    'created_at'       => current_time('mysql'),
                    'updated_at'       => current_time('mysql'),
                ],
                [
                    '%d', '%s', '%f', '%f', '%f', '%f', '%f', '%s', '%d', '%s',
                    '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s',
                    '%d', '%s', '%s'
                ]
            );

            if ($result === false || $wpdb->last_error) {
                throw new Exception(__('خطا در ثبت درخواست برداشت.', 'golden-dashboard'));
            }

            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('db_error', $e->getMessage());
        }

        $request_id = $wpdb->insert_id;
        self::log_security_event($user_id, 'withdraw_request_created', [
            'request_id' => $request_id,
            'amount'     => $amount,
            'fee'        => $fee_amount,
            'net'        => $net_amount,
            'tracking_code' => $tracking_code
        ]);

        self::notify_admin($user_id, $amount, $tracking_code, $request_id);
        return $request_id;
    }

    public static function approve($request_id, $payment_data = []) {
        global $wpdb;

        $request_id = absint($request_id);
        $table = $wpdb->prefix . 'gd_wallet_transactions';

        $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $request_id));
        if (!$request || $request->status !== 'pending' || $request->transaction_type !== 'withdraw_request') {
            return new WP_Error('invalid_request', __('درخواست نامعتبر یا قبلاً پردازش شده است.', 'golden-dashboard'));
        }

        $user_id = (int) $request->user_id;
        $amount = (float) $request->amount;
        $net_amount = (float) $request->net_amount;

        
        
        
        
        $result = $wpdb->update(
            $table,
            [
                'status'               => 'completed',
                'admin_note'           => sanitize_text_field($payment_data['admin_note'] ?? ''),
                'bank_transaction_id'  => sanitize_text_field($payment_data['bank_transaction_id'] ?? ''),
                'bank_date'            => sanitize_text_field($payment_data['bank_date'] ?? ''),
                'description'          => sprintf(
                    /* translators: 1: amount, 2: fee amount, 3: net amount, 4: tracking code */
                    __('برداشت تایید شده به مبلغ %1$s (کارمزد: %2$s - مبلغ واریزی: %3$s) - کد پیگیری: %4$s', 'golden-dashboard'),
                    gdb_price_plain($amount),
                    gdb_price_plain($request->fee_amount),
                    gdb_price_plain($net_amount),
                    $request->tracking_code
                ),
                'updated_at'           => current_time('mysql'),
            ],
            ['id' => $request_id],
            ['%s', '%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );

        if ($result === false || $wpdb->last_error) {
            return new WP_Error('approve_failed', __('خطا در ثبت تایید برداشت.', 'golden-dashboard'));
        }

        self::log_security_event($user_id, 'withdraw_approved', [
            'request_id' => $request_id,
            'amount'     => $amount,
            'fee'        => $request->fee_amount,
            'net'        => $net_amount,
            'tracking_code' => $request->tracking_code
        ]);
        self::notify_user($user_id, 'approved', $request_id);

        return true;
    }

    public static function reject($request_id, $reason = '') {
        global $wpdb;

        $request_id = absint($request_id);
        $table = $wpdb->prefix . 'gd_wallet_transactions';
        $wallet_table = $wpdb->prefix . 'gd_user_wallet';

        $request = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $request_id));
        if (!$request || $request->status !== 'pending' || $request->transaction_type !== 'withdraw_request') {
            return new WP_Error('invalid_request', __('درخواست نامعتبر یا قبلاً پردازش شده است.', 'golden-dashboard'));
        }

        $user_id = (int) $request->user_id;
        $amount = (float) $request->amount;

        
        
        $wpdb->query('START TRANSACTION');
        try {
            $wallet = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wallet_table} WHERE user_id = %d FOR UPDATE", $user_id));
            if (!$wallet) {
                throw new Exception(__('کیف پول کاربر یافت نشد.', 'golden-dashboard'));
            }
            $balance_before = (float) $wallet->balance;
            $new_balance = $balance_before + $amount;

            $wpdb->update(
                $wallet_table,
                [
                    'balance'     => $new_balance,
                    'total_debit' => max(0, (float) $wallet->total_debit - $amount),
                    'updated_at'  => current_time('mysql'),
                ],
                ['user_id' => $user_id],
                ['%f', '%f', '%s'],
                ['%d']
            );

            $wpdb->update(
                $table,
                [
                    'status'     => 'rejected',
                    'admin_note' => sanitize_text_field($reason),
                    /* translators: 1: amount, 2: tracking code */
                    'description'=> sprintf(__('درخواست برداشت به مبلغ %1$s رد شد و به کیف پول بازگردانده شد - کد پیگیری: %2$s', 'golden-dashboard'), gdb_price_plain($amount), $request->tracking_code),
                    'updated_at' => current_time('mysql'),
                ],
                ['id' => $request_id],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );

            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            $refund_inserted = $wpdb->insert(
                $table,
                [
                    'user_id'             => $user_id,
                    'type'                => 'credit',
                    'amount'              => $amount,
                    'fee_amount'          => 0,
                    'net_amount'          => $amount,
                    'balance_before'      => $balance_before,
                    'balance_after'       => $new_balance,
                    'transaction_type'    => 'withdraw_rejected_refund',
                    'reference_id'        => $request_id,
                    'description'         => sprintf(
                        /* translators: %s: tracking code */
                        __('بازگشت وجه به دلیل رد درخواست برداشت - کد پیگیری: %s', 'golden-dashboard'),
                        $request->tracking_code
                    ),
                    'status'              => 'completed',
                    'tracking_code'       => $request->tracking_code,
                    'admin_note'          => sanitize_text_field($reason),
                    'bank_transaction_id' => '',
                    'bank_date'           => '',
                    'meta_data'           => '',
                    'created_by'          => get_current_user_id(),
                    'ip_address'          => sanitize_text_field((isset($_SERVER['REMOTE_ADDR']) ? wp_unslash($_SERVER['REMOTE_ADDR']) : '')),
                    'user_agent'          => substr(sanitize_text_field((isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '')), 0, 255),
                    'session_id'          => function_exists('session_id') && session_status() === PHP_SESSION_ACTIVE ? session_id() : '',
                    'is_suspicious'       => 0,
                    'created_at'          => current_time('mysql'),
                    'updated_at'          => current_time('mysql'),
                ],
                [
                    '%d', '%s', '%f', '%f', '%f', '%f', '%f', '%s', '%d', '%s',
                    '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s',
                    '%d', '%s', '%s'
                ]
            );

            if ($refund_inserted === false || $wpdb->last_error) {
                throw new Exception($wpdb->last_error ?: __('خطا در ثبت رکورد بازگشت وجه.', 'golden-dashboard'));
            }

            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('db_error', __('خطا در رد درخواست.', 'golden-dashboard'));
        }

        self::log_security_event($user_id, 'withdraw_rejected', [
            'request_id' => $request_id,
            'amount'     => $amount,
            'reason'     => $reason
        ]);
        self::notify_user($user_id, 'rejected', $request_id, $reason);

        return true;
    }

    public static function get_requests($filters = [], $per_page = 20, $page = 1) {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_transactions';
        $where = ['transaction_type = "withdraw_request"'];

        if (!empty($filters['status'])) {
            $where[] = $wpdb->prepare("status = %s", $filters['status']);
        }
        if (!empty($filters['user_id'])) {
            $where[] = $wpdb->prepare("user_id = %d", absint($filters['user_id']));
        }
        if (!empty($filters['date_from'])) {
            $where[] = $wpdb->prepare("created_at >= %s", $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $where[] = $wpdb->prepare("created_at <= %s", $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where[] = $wpdb->prepare(
                "(tracking_code LIKE %s OR user_id IN (SELECT ID FROM {$wpdb->users} WHERE display_name LIKE %s OR user_login LIKE %s OR user_email LIKE %s))",
                $search,
                $search,
                $search,
                $search
            );
        }

        if (!empty($filters['tracking'])) {
            $tracking = $wpdb->esc_like($filters['tracking']);
            $where[] = $wpdb->prepare("tracking_code LIKE %s", '%' . $tracking . '%');
        }

        $where_sql = implode(' AND ', $where);
        $offset = ($page - 1) * $per_page;
        $sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";
        $results = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));
        $total_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $total = (int) $wpdb->get_var($total_sql);

        return [
            'items' => $results,
            'total' => $total,
            'pages' => ceil($total / $per_page),
        ];
    }

    private static function generate_tracking_code() {
        return sprintf('%012d', wp_rand(0, 999999999999));
    }

    private static function log_security_event($user_id, $event_type, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_security_log';
        $wpdb->insert(
            $table,
            [
                'user_id'      => $user_id,
                'event_type'   => $event_type,
                'severity'     => 'low',
                'message'      => sprintf('%s: %s', $event_type, wp_json_encode($data)),
                'ip_address'   => sanitize_text_field((isset($_SERVER['REMOTE_ADDR']) ? wp_unslash($_SERVER['REMOTE_ADDR']) : '')),
                'user_agent'   => substr(sanitize_text_field((isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '')), 0, 255),
                'request_data' => wp_json_encode($data),
                'created_at'   => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }

    private static function notify_admin($user_id, $amount, $tracking_code, $request_id) {
        if (get_option('gdb_enable_admin_email', 'yes') !== 'yes') {
            return;
        }
        $admin_email = get_option('admin_email');
        $user_info = get_userdata($user_id);
        /* translators: %s: tracking code */
        $subject = sprintf(__('درخواست برداشت جدید #%s', 'golden-dashboard'), $tracking_code);
        $message = sprintf(
            /* translators: 1: user display name, 2: amount, 3: tracking code */
            __('کاربر %1$s درخواست برداشت به مبلغ %2$s ثبت کرد. کد پیگیری: %3$s
برای بررسی و مدیریت، به پیشخوان مدیریت مراجعه کنید.', 'golden-dashboard'),
            $user_info->display_name,
            gdb_price_plain($amount),
            $tracking_code
        );
        wp_mail($admin_email, $subject, $message);
    }

    private static function notify_user($user_id, $status, $request_id, $reason = '') {
        if (get_option('gdb_enable_user_email', 'yes') !== 'yes') {
            return;
        }
        $user = get_userdata($user_id);
        if (!$user) return;

        $subject = '';
        $message = '';
        switch ($status) {
            case 'approved':
                $subject = __('درخواست برداشت شما تایید شد', 'golden-dashboard');
                $message = __('درخواست برداشت شما با موفقیت تایید و مبلغ به حساب شما واریز شد.', 'golden-dashboard');
                break;
            case 'rejected':
                $subject = __('درخواست برداشت شما رد شد', 'golden-dashboard');
                /* translators: %s: rejection reason */
                $message = sprintf(__('درخواست برداشت شما رد شد. دلیل: %s', 'golden-dashboard'), $reason ?: 'نامشخص');
                break;
        }
        if ($subject && $message) {
            wp_mail($user->user_email, $subject, $message);
        }
    }

    public static function install() {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_transactions';

        $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}");
        $new_columns = [
            'status'              => "VARCHAR(20) NOT NULL DEFAULT 'completed'",
            'tracking_code'       => "VARCHAR(12) DEFAULT ''",
            'admin_note'          => "TEXT",
            'bank_transaction_id' => "VARCHAR(100) DEFAULT ''",
            'bank_date'           => "VARCHAR(50) DEFAULT ''",
            'meta_data'           => "TEXT",
            'updated_at'          => "DATETIME DEFAULT NULL",
            'fee_amount'          => "decimal(20,2) DEFAULT '0.00'",
            'net_amount'          => "decimal(20,2) DEFAULT '0.00'",
        ];

        foreach ($new_columns as $col => $definition) {
            if (!in_array($col, $columns)) {
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$col} {$definition}");
            }
        }

        
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$table}");
        $existing_indexes = [];
        foreach ($indexes as $idx) {
            $existing_indexes[] = $idx->Key_name;
        }

        if (!in_array('idx_status', $existing_indexes)) {
            $wpdb->query("ALTER TABLE {$table} ADD INDEX idx_status (status)");
        }
        if (!in_array('idx_tracking', $existing_indexes)) {
            $wpdb->query("ALTER TABLE {$table} ADD INDEX idx_tracking (tracking_code)");
        }
        if (!in_array('idx_transaction_type', $existing_indexes)) {
            $wpdb->query("ALTER TABLE {$table} ADD INDEX idx_transaction_type (transaction_type)");
        }
    }
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange

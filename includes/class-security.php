<?php

if (!defined('ABSPATH')) {
    exit;
}

class GDB_Security {



    public static function get_client_ip() {
        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
    }

    private static function get_user_agent() {
        return substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }





    public static function log_event($user_id, $event_type, $message, $severity = 'low', $data = []) {



        if ($severity === 'low' && get_option('gdb_security_log_all_transactions', 'yes') !== 'yes') {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_security_log';

        $wpdb->insert(
            $table,
            [
                'user_id'      => absint($user_id),
                'event_type'   => $event_type,
                'severity'     => $severity,
                'message'      => $message,
                'ip_address'   => self::get_client_ip(),
                'user_agent'   => self::get_user_agent(),
                'request_data' => !empty($data) ? wp_json_encode($data) : null,
                'created_at'   => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }





    public static function check_rate_limit($identifier, $action_type) {
        if (get_option('gdb_security_rate_limit_enabled', '') !== '1') {
            return true;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_rate_limit';

        $window   = max(1, (int) get_option('gdb_security_rate_limit_window', 60));
        $max_try  = max(1, (int) get_option('gdb_security_rate_limit_max_attempts', 5));
        $block_for = max(1, (int) get_option('gdb_security_rate_limit_block_duration', 300));

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE identifier = %s AND action_type = %s",
            $identifier,
            $action_type
        ));

        $now = current_time('timestamp');


        if ($row && $row->blocked_until && strtotime($row->blocked_until) > $now) {
            return new WP_Error(
                'rate_limited',
                sprintf(
                    __('تعداد تلاش‌های شما بیش از حد مجاز بوده است. لطفاً %d ثانیه دیگر دوباره تلاش کنید.', 'golden-dashboard'),
                    strtotime($row->blocked_until) - $now
                )
            );
        }

        if (!$row) {
            $wpdb->insert($table, [
                'identifier'    => $identifier,
                'action_type'   => $action_type,
                'attempt_count' => 1,
                'last_attempt'  => current_time('mysql'),
                'blocked_until' => null,
            ], ['%s', '%s', '%d', '%s', '%s']);
            return true;
        }

        $last_attempt_time = strtotime($row->last_attempt);
        $window_expired = ($now - $last_attempt_time) > $window;

        if ($window_expired) {

            $wpdb->update($table, [
                'attempt_count' => 1,
                'last_attempt'  => current_time('mysql'),
                'blocked_until' => null,
            ], ['id' => $row->id], ['%d', '%s', '%s'], ['%d']);
            return true;
        }

        $new_count = (int) $row->attempt_count + 1;

        if ($new_count > $max_try) {
            $blocked_until = date('Y-m-d H:i:s', $now + $block_for);
            $wpdb->update($table, [
                'attempt_count' => $new_count,
                'last_attempt'  => current_time('mysql'),
                'blocked_until' => $blocked_until,
            ], ['id' => $row->id], ['%d', '%s', '%s'], ['%d']);

            self::log_event(0, 'rate_limit_blocked', sprintf(
                '%s برای %s به دلیل تجاوز از حد مجاز تلاش (%d بار در %d ثانیه) به مدت %d ثانیه مسدود شد.',
                $identifier, $action_type, $new_count, $window, $block_for
            ), 'medium', ['identifier' => $identifier, 'action_type' => $action_type, 'attempts' => $new_count]);

            return new WP_Error(
                'rate_limited',
                sprintf(__('تعداد تلاش‌های شما بیش از حد مجاز بود. لطفاً %d ثانیه دیگر دوباره تلاش کنید.', 'golden-dashboard'), $block_for)
            );
        }

        $wpdb->update($table, [
            'attempt_count' => $new_count,
            'last_attempt'  => current_time('mysql'),
        ], ['id' => $row->id], ['%d', '%s'], ['%d']);

        return true;
    }



    public static function is_ip_blocked($ip) {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_blocked_ips';
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE ip_address = %s",
            $ip
        ));
        return $count > 0;
    }

    public static function block_ip($ip, $reason = '', $blocked_by = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_blocked_ips';

        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO {$table} (ip_address, reason, blocked_by, created_at) VALUES (%s, %s, %d, %s)
             ON DUPLICATE KEY UPDATE reason = VALUES(reason), blocked_by = VALUES(blocked_by)",
            $ip, $reason, $blocked_by, current_time('mysql')
        ));

        self::log_event($blocked_by, 'ip_blocked', sprintf('IP آدرس %s توسط مدیر مسدود شد.', $ip), 'medium', ['ip' => $ip, 'reason' => $reason]);

        return $result !== false;
    }

    public static function unblock_ip($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_blocked_ips';
        $ip = $wpdb->get_var($wpdb->prepare("SELECT ip_address FROM {$table} WHERE id = %d", $id));
        $result = $wpdb->delete($table, ['id' => absint($id)], ['%d']);

        if ($result && $ip) {
            self::log_event(0, 'ip_unblocked', sprintf('IP آدرس %s رفع مسدودی شد.', $ip), 'low', ['ip' => $ip]);
        }

        return (bool) $result;
    }

    public static function get_blocked_ips() {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_blocked_ips';
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC");
    }



    public static function is_suspicious_amount($amount) {
        $threshold = (float) get_option('gdb_security_suspicious_amount_threshold', 0);
        return $threshold > 0 && (float) $amount >= $threshold;
    }

    public static function requires_verification($amount) {
        $threshold = (float) get_option('gdb_security_require_verification_above', 0);
        return $threshold > 0 && (float) $amount >= $threshold;
    }

    public static function check_max_transaction_amount($amount) {
        $max = (float) get_option('gdb_security_max_transaction_amount', 0);
        if ($max > 0 && (float) $amount > $max) {
            return new WP_Error('max_transaction_amount', sprintf(
                __('مبلغ تراکنش بیشتر از حداکثر مجاز (%s) است.', 'golden-dashboard'),
                gdb_price_plain($max)
            ));
        }
        return true;
    }

    public static function check_daily_transaction_limit($user_id) {
        $max = (int) get_option('gdb_security_max_daily_transactions', 0);
        if ($max <= 0) {
            return true;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_transactions';
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND created_at >= %s",
            $user_id,
            date('Y-m-d H:i:s', current_time('timestamp') - DAY_IN_SECONDS)
        ));

        if ($count >= $max) {
            return new WP_Error('max_daily_transactions', sprintf(
                __('شما به سقف مجاز تعداد تراکنش روزانه (%d مورد) رسیده‌اید. لطفاً فردا دوباره تلاش کنید.', 'golden-dashboard'),
                $max
            ));
        }
        return true;
    }



    public static function flag_transaction_if_suspicious($transaction_id, $amount, $user_id, $context = '') {
        if (!self::is_suspicious_amount($amount)) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_transactions';
        $wpdb->update($table, ['is_suspicious' => 1], ['id' => absint($transaction_id)], ['%d'], ['%d']);

        self::log_event($user_id, 'suspicious_amount_detected', sprintf(
            'تراکنش شماره %d (%s) به دلیل مبلغ بالا (%s) به‌عنوان مشکوک علامت‌گذاری شد.',
            $transaction_id,
            $context,
            gdb_price_plain($amount)
        ), 'high', ['transaction_id' => $transaction_id, 'amount' => $amount, 'context' => $context]);

        if (get_option('gdb_security_alert_admin_on_suspicious', '1') === '1') {
            self::notify_admin_suspicious($user_id, $amount, $transaction_id, $context);
        }

        return true;
    }

    private static function notify_admin_suspicious($user_id, $amount, $transaction_id, $context) {
        $admin_email = get_option('admin_email');
        if (!$admin_email) {
            return;
        }
        $user_info = get_userdata($user_id);
        $user_name = $user_info ? $user_info->display_name : $user_id;

        $subject = __('هشدار: تراکنش مشکوک در کیف پول', 'golden-dashboard');
        $message = sprintf(
            __("یک تراکنش با مبلغ بالا شناسایی شد:\n\nکاربر: %s (شناسه %d)\nمبلغ: %s\nنوع: %s\nشناسه تراکنش: %d\n\nلطفاً از پنل مدیریت بررسی کنید.", 'golden-dashboard'),
            $user_name,
            $user_id,
            gdb_price_plain($amount),
            $context,
            $transaction_id
        );
        wp_mail($admin_email, $subject, $message);
    }



    public static function get_stats_24h() {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_security_log';
        $since = date('Y-m-d H:i:s', current_time('timestamp') - DAY_IN_SECONDS);

        $critical = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE severity = 'critical' AND created_at >= %s", $since
        ));
        $high = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE severity = 'high' AND created_at >= %s", $since
        ));
        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s", $since
        ));

        return ['critical' => $critical, 'high' => $high, 'total' => $total];
    }
}

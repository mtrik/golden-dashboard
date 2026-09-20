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

class GDB_Wallet
{
    private static $wallet_cache = [];

    private static function wallet_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'gd_user_wallet';
    }

    private static function transaction_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'gd_wallet_transactions';
    }

    private static function table_exists($table)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table;
    }

    private static function user($user_id = 0)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        return absint($user_id) > 0 ? absint($user_id) : 0;
    }

    public static function exists($user_id = 0)
    {
        global $wpdb;
        $user_id = self::user($user_id);
        if (!$user_id || !self::table_exists(self::wallet_table())) {
            return false;
        }
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . self::wallet_table() . " WHERE user_id=%d LIMIT 1",
            $user_id
        ));
    }



    public static function balance($user_id = 0)
    {
        global $wpdb;
        $user_id = self::user($user_id);
        if (!$user_id || !self::table_exists(self::wallet_table())) {
            return 0;
        }
        $balance = $wpdb->get_var($wpdb->prepare(
            "SELECT balance FROM " . self::wallet_table() . " WHERE user_id=%d LIMIT 1",
            $user_id
        ));
        return $balance === null ? 0 : (float) $balance;
    }

    public static function get_balance($user_id = 0)
    {
        return self::balance($user_id);
    }



    public static function balance_html($user_id = 0)
    {
        return gdb_price(self::balance($user_id));
    }



    public static function price($amount)
    {
        return gdb_price((float) $amount);
    }

    public static function total_credit($user_id = 0)
    {
        global $wpdb;
        $user_id = self::user($user_id);
        if (!$user_id || !self::table_exists(self::wallet_table())) {
            return 0;
        }
        return (float) $wpdb->get_var($wpdb->prepare(
            "SELECT total_credit FROM " . self::wallet_table() . " WHERE user_id=%d",
            $user_id
        ));
    }

    public static function total_debit($user_id = 0)
    {
        global $wpdb;
        $user_id = self::user($user_id);
        if (!$user_id || !self::table_exists(self::wallet_table())) {
            return 0;
        }
        return (float) $wpdb->get_var($wpdb->prepare(
            "SELECT total_debit FROM " . self::wallet_table() . " WHERE user_id=%d",
            $user_id
        ));
    }

    public static function wallet($user_id = 0)
    {
        global $wpdb;
        $user_id = self::user($user_id);
        if (!$user_id || !self::table_exists(self::wallet_table())) {
            return null;
        }
        if (isset(self::$wallet_cache[$user_id])) {
            return self::$wallet_cache[$user_id];
        }
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::wallet_table() . " WHERE user_id=%d LIMIT 1",
            $user_id
        ));
        self::$wallet_cache[$user_id] = $result;
        return $result;
    }

    public static function transaction_count($user_id = 0)
    {
        global $wpdb;
        $user_id = self::user($user_id);
        if (!$user_id || !self::table_exists(self::transaction_table())) {
            return 0;
        }
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::transaction_table() . " WHERE user_id=%d",
            $user_id
        ));
    }

    public static function get_transactions($user_id = 0, $limit = 10)
    {
        global $wpdb;
        $user_id = self::user($user_id);
        if (!$user_id || !self::table_exists(self::transaction_table())) {
            return [];
        }
        $limit = absint($limit) ?: 10;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . self::transaction_table() . " WHERE user_id=%d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        ));
    }

    public static function last_transaction($user_id = 0)
    {
        $items = self::get_transactions($user_id, 1);
        return empty($items) ? false : $items[0];
    }

    public static function credits($user_id = 0)
    {
        global $wpdb;
        $user_id = self::user($user_id);
        if (!$user_id || !self::table_exists(self::transaction_table())) {
            return [];
        }
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . self::transaction_table() . " WHERE user_id=%d AND type='credit' ORDER BY created_at DESC",
            $user_id
        ));
    }

    public static function debits($user_id = 0)
    {
        global $wpdb;
        $user_id = self::user($user_id);
        if (!$user_id || !self::table_exists(self::transaction_table())) {
            return [];
        }
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . self::transaction_table() . " WHERE user_id=%d AND type='debit' ORDER BY created_at DESC",
            $user_id
        ));
    }

    public static function last_balance($user_id = 0)
    {
        $tx = self::last_transaction($user_id);
        return $tx ? (float) $tx->balance_after : self::balance($user_id);
    }

    public static function get_transactions_by_type_and_status($user_id, $type, $status = '')
    {
        global $wpdb;
        $user_id = absint($user_id);
        if (!$user_id || !self::table_exists(self::transaction_table())) {
            return [];
        }
        $sql = "SELECT * FROM " . self::transaction_table() . " WHERE user_id = %d AND transaction_type = %s";
        $params = [$user_id, $type];
        if ($status) {
            $sql .= " AND status = %s";
            $params[] = $status;
        }
        $sql .= " ORDER BY id DESC";
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    public static function get_formatted_balance($user_id = 0)
    {
        return self::balance_html($user_id);
    }

    public static function get_account_url($user_id = 0)
    {
        return get_permalink(wc_get_page_id('myaccount'));
    }

    public static function get_transaction_label($transaction_type, $type)
    {
        $tx = new stdClass();
        $tx->transaction_type = $transaction_type;
        $tx->type = $type;
        return gdb_transaction_label($tx, ($type === 'credit'));
    }

    public static function get_transaction_icon($transaction_type, $type)
    {
        $icon = '';
        if ($type === 'credit') {
            $icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 4V20M20 12H4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
        } else {
            $icon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 20V4M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
        }
        return $icon;
    }

    public static function format_transaction_date($datetime)
    {
        return gdb_date_jalali($datetime, true);
    }
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange

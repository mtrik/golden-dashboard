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

class GDB_Cashback {

    public function __construct() {

        add_action('woocommerce_order_status_changed', [$this, 'maybe_apply_order_cashback'], 20, 4);
    }



    public static function maybe_apply($user_id, $base_amount, $context, $reference_id = 0) {
        if (!in_array($context, ['topup', 'gold', 'order'])) {
            return false;
        }

        $enabled = get_option('gdb_cashback_' . $context . '_enabled', '');
        if ($enabled !== '1') {
            return false;
        }

        $percent = (float) get_option('gdb_cashback_' . $context . '_percent', 0);
        if ($percent <= 0 || $base_amount <= 0) {
            return false;
        }

        $cashback_amount = round(($base_amount * $percent) / 100, 2);
        if ($cashback_amount <= 0) {
            return false;
        }

        global $wpdb;
        $table_wallet = $wpdb->prefix . 'gd_user_wallet';
        $table_trans = $wpdb->prefix . 'gd_wallet_transactions';

        $balance_before = GDB_Wallet::balance($user_id);
        $balance_after = $balance_before + $cashback_amount;

        $wpdb->update($table_wallet, [
            'balance'      => $balance_after,
            'total_credit' => $wpdb->get_var($wpdb->prepare("SELECT total_credit FROM {$table_wallet} WHERE user_id=%d", $user_id)) + $cashback_amount,
            'updated_at'   => current_time('mysql'),
        ], ['user_id' => $user_id], ['%f', '%f', '%s'], ['%d']);

        $tracking_code = function_exists('gdb_generate_tracking_code') ? gdb_generate_tracking_code() : wp_generate_password(12, false);

        $context_labels = [
            'topup' => __('شارژ کیف پول', 'golden-dashboard'),
            'gold'  => __('خرید طلا', 'golden-dashboard'),
            'order' => __('خرید از فروشگاه', 'golden-dashboard'),
        ];

        $description = sprintf(
            /* translators: 1: cashback percent, 2: context label, 3: reference ID, 4: tracking code */
            __('کش‌بک %1$s٪ بابت %2$s (مرجع #%3$d) - کد پیگیری: %4$s', 'golden-dashboard'),
            rtrim(rtrim(number_format($percent, 2), '0'), '.'),
            $context_labels[$context],
            $reference_id,
            $tracking_code
        );

        $wpdb->insert($table_trans, [
            'user_id'          => $user_id,
            'type'             => 'credit',
            'amount'           => $cashback_amount,
            'balance_before'   => $balance_before,
            'balance_after'    => $balance_after,
            'transaction_type' => 'cashback',
            'reference_id'     => $reference_id,
            'description'      => $description,
            'created_by'       => 0,
            'ip_address'       => GDB_Security::get_client_ip(),
            'user_agent'       => substr(sanitize_text_field((isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '')), 0, 255),
            'status'           => 'completed',
            'tracking_code'    => $tracking_code,
            'meta_data'        => maybe_serialize(['context' => $context, 'percent' => $percent, 'base_amount' => $base_amount]),
            'created_at'       => current_time('mysql'),
            'updated_at'       => current_time('mysql'),
        ]);

        GDB_Security::log_event($user_id, 'cashback_credited', $description, 'low', [
            'context' => $context, 'reference_id' => $reference_id, 'amount' => $cashback_amount,
        ]);

        return true;
    }



    public static function reverse_for_order($order_id) {
        global $wpdb;
        $table_trans = $wpdb->prefix . 'gd_wallet_transactions';

        $cashback_tx = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_trans} WHERE transaction_type = 'cashback' AND reference_id = %d AND status = 'completed' LIMIT 1",
            $order_id
        ));

        if (!$cashback_tx) {
            return false;
        }








        $already_reversed = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_trans} WHERE transaction_type = 'cashback_reversal' AND reference_id = %d",
            $order_id
        ));
        if ($already_reversed > 0) {
            return false;
        }

        $user_id = (int) $cashback_tx->user_id;
        $amount = (float) $cashback_tx->amount;

        $table_wallet = $wpdb->prefix . 'gd_user_wallet';

        $wpdb->query('START TRANSACTION');
        try {
            $wallet = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_wallet} WHERE user_id = %d FOR UPDATE", $user_id));
            $balance_before = $wallet ? (float) $wallet->balance : GDB_Wallet::balance($user_id);
            $balance_after = max(0, $balance_before - $amount);

            $wpdb->update($table_wallet, [
                'balance'    => $balance_after,
                'updated_at' => current_time('mysql'),
            ], ['user_id' => $user_id], ['%f', '%s'], ['%d']);

            $tracking_code = function_exists('gdb_generate_tracking_code') ? gdb_generate_tracking_code() : wp_generate_password(12, false);

            $wpdb->insert($table_trans, [
                'user_id'          => $user_id,
                'type'             => 'debit',
                'amount'           => $amount,
                'balance_before'   => $balance_before,
                'balance_after'    => $balance_after,
                'transaction_type' => 'cashback_reversal',
                'reference_id'     => $order_id,
                'description'      => sprintf(
                    /* translators: 1: order ID, 2: tracking code */
                    __('برداشت کش‌بک به دلیل لغو/ناموفق شدن سفارش شماره %1$d - کد پیگیری: %2$s', 'golden-dashboard'),
                    $order_id,
                    $tracking_code
                ),
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
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }

        GDB_Security::log_event($user_id, 'cashback_reversed', sprintf(
            'کش‌بک %s تومان به دلیل لغو سفارش شماره %d از کیف پول کاربر برداشت شد.',
            $amount, $order_id
        ), 'medium', ['order_id' => $order_id, 'amount' => $amount]);

        return true;
    }



    public function maybe_apply_order_cashback($order_id, $from, $to, $order) {
        if (!in_array($to, ['completed', 'processing'])) {
            return;
        }
        if ($order->get_meta('_gdb_is_topup') === 'yes' || $order->get_meta('_gdb_is_gold_purchase') === 'yes') {
            return;
        }
        if ($order->get_meta('_gdb_order_cashback_applied') === 'yes') {
            return;
        }

        $user_id = $order->get_customer_id();
        if (!$user_id) {
            return;
        }

        $applied = self::maybe_apply($user_id, (float) $order->get_total(), 'order', $order_id);
        if ($applied) {
            $order->update_meta_data('_gdb_order_cashback_applied', 'yes');
            $order->save();
        }
    }
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange

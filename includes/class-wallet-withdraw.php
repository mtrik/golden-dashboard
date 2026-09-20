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

class GDB_Wallet_Withdraw {

    public static function process_withdraw($user_id, $amount) {
        global $wpdb;

        $user_id = absint($user_id);
        
        $amount = floatval(gdb_storage_amount($amount));

        if ($user_id <= 0 || $amount <= 0) {
            return new WP_Error('invalid_input', __('مبلغ یا کاربر نامعتبر است.', 'golden-dashboard'));
        }

        $min_amount = (float) get_option('gdb_withdraw_min_amount', 1000);
        $max_amount = (float) get_option('gdb_withdraw_max_amount', 50000000);

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

        $table_wallet       = $wpdb->prefix . 'gd_user_wallet';
        $table_transactions = $wpdb->prefix . 'gd_wallet_transactions';
        $table_security     = $wpdb->prefix . 'gd_wallet_security_log';

        $wpdb->query('START TRANSACTION');

        try {
            $wallet = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_wallet} WHERE user_id = %d FOR UPDATE", $user_id));
            if (!$wallet) {
                throw new Exception(__('کیف پول کاربر یافت نشد.', 'golden-dashboard'));
            }

            $balance_before = (float) $wallet->balance;
            $balance_after = $balance_before - $amount;
            if ($balance_after < 0) {
                throw new Exception(__('موجودی کافی نیست.', 'golden-dashboard'));
            }

            $wpdb->update(
                $table_wallet,
                ['balance' => $balance_after, 'total_debit' => (float) $wallet->total_debit + $amount, 'updated_at' => current_time('mysql')],
                ['user_id' => $user_id],
                ['%f', '%f', '%s'],
                ['%d']
            );
            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            /* translators: %s: withdrawal amount */
            $description = sprintf(__('برداشت مستقیم از کیف پول به مبلغ %s', 'golden-dashboard'), gdb_price_plain($amount));
            $wpdb->insert(
                $table_transactions,
                [
                    'user_id'         => $user_id,
                    'type'            => 'debit',
                    'amount'          => $amount,
                    'balance_before'  => $balance_before,
                    'balance_after'   => $balance_after,
                    'transaction_type' => 'withdraw',
                    'reference_id'    => 0,
                    'description'     => $description,
                    'created_by'      => $user_id,
                    'ip_address'      => sanitize_text_field((isset($_SERVER['REMOTE_ADDR']) ? wp_unslash($_SERVER['REMOTE_ADDR']) : '')),
                    'user_agent'      => substr(sanitize_text_field((isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '')), 0, 255),
                    'session_id'      => function_exists('session_id') && session_status() === PHP_SESSION_ACTIVE ? session_id() : '',
                    'is_suspicious'   => 0,
                    'created_at'      => current_time('mysql')
                ],
                ['%d', '%s', '%f', '%f', '%f', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s']
            );
            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }

            $transaction_id = $wpdb->insert_id;

            $wpdb->insert(
                $table_security,
                [
                    'user_id'      => $user_id,
                    'event_type'   => 'wallet_debited',
                    'severity'     => 'low',
                    'message'      => sprintf('برداشت مستقیم از کیف پول به مبلغ %s', gdb_price_plain($amount)),
                    'ip_address'   => sanitize_text_field((isset($_SERVER['REMOTE_ADDR']) ? wp_unslash($_SERVER['REMOTE_ADDR']) : '')),
                    'user_agent'   => substr(sanitize_text_field((isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash($_SERVER['HTTP_USER_AGENT']) : '')), 0, 255),
                    'request_data' => wp_json_encode(['amount' => $amount]),
                    'created_at'   => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );

            $wpdb->query('COMMIT');

            return [
                'success'          => true,
                'balance_before'   => $balance_before,
                'balance_after'    => $balance_after,
                'amount'           => $amount,
                'transaction_id'   => $transaction_id,
                'message'          => __('برداشت با موفقیت انجام شد.', 'golden-dashboard')
            ];

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wc_get_logger()->error(sprintf('خطا در برداشت کیف پول (کاربر %d): %s', $user_id, $e->getMessage()), ['source' => 'gdb-wallet-withdraw']);
            return new WP_Error('withdraw_failed', $e->getMessage());
        }
    }
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange

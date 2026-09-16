<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$delete_all = get_option('gdb_uninstall_delete_tables', false);

if (class_exists('GDB_Loader')) {
    GDB_Loader::uninstall();
}

if (!$delete_all) {
    return;
}

$tables = [
    $wpdb->prefix . 'gd_user_wallet',
    $wpdb->prefix . 'gd_wallet_transactions',
    $wpdb->prefix . 'gd_wallet_security_log',
    $wpdb->prefix . 'gd_audit_log',
    $wpdb->prefix . 'gd_wallet_rate_limit',
    $wpdb->prefix . 'gd_wallet_blocked_ips',
    $wpdb->prefix . 'gd_gold_wallet_types',
    $wpdb->prefix . 'gd_user_metal_wallet',
    $wpdb->prefix . 'gd_metal_transactions',
];
foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}

$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'gdb\\_%'");

$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '\\_gdb\\_%' OR meta_key LIKE 'gdb\\_%'");

$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '\\_gdb\\_%'");
if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')
    && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
) {
    $hpos_meta_table = $wpdb->prefix . 'wc_orders_meta';
    $hpos_table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $hpos_meta_table));
    if ($hpos_table_exists) {
        $wpdb->query("DELETE FROM `{$hpos_meta_table}` WHERE meta_key LIKE '\\_gdb\\_%'");
    }
}

$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_gdb\\_%' OR option_name LIKE '\\_transient\\_timeout\\_gdb\\_%'");
if (is_multisite()) {
    $wpdb->query("DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE '\\_site\\_transient\\_gdb\\_%' OR meta_key LIKE '\\_site\\_transient\\_timeout\\_gdb\\_%'");
}

$possible_cron_hooks = ['gdb_daily_cleanup', 'gdb_gold_price_update', 'gdb_cron_job'];
foreach ($possible_cron_hooks as $hook) {
    $timestamp = wp_next_scheduled($hook);
    if ($timestamp) {
        wp_unschedule_event($timestamp, $hook);
    }
    wp_clear_scheduled_hook($hook);
}

$possible_caps = ['manage_gdb_wallet', 'gdb_approve_withdrawals'];
foreach (wp_roles()->roles as $role_slug => $role_info) {
    $role = get_role($role_slug);
    if (!$role) {
        continue;
    }
    foreach ($possible_caps as $cap) {
        if (isset($role->capabilities[$cap])) {
            $role->remove_cap($cap);
        }
    }
}

wp_cache_flush();

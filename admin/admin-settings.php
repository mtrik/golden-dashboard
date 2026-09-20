<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(esc_html__('شما اجازه دسترسی به این صفحه را ندارید.', 'golden-dashboard'));
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selection; no data is written or changed here.
$gdb_active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'general';
$gdb_tabs = [
    'general'     => __('عمومی', 'golden-dashboard'),
    'wallet'      => __('کیف پول', 'golden-dashboard'),
    'gold-wallet' => __('کیف پول طلا', 'golden-dashboard'),
    'cashback'    => __('کش‌بک', 'golden-dashboard'),
    'security'    => __('تنظیمات امنیتی', 'golden-dashboard'),
];
if (!array_key_exists($gdb_active_tab, $gdb_tabs)) {
    $gdb_active_tab = 'general';
}

?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('تنظیمات گلدن داشبورد', 'golden-dashboard'); ?></h1>

    <?php
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only display of a status message passed via redirect after an action whose own nonce was already verified; no data is written or changed here.
    if (isset($_GET['gdb_message'])) :
    ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html(sanitize_text_field(wp_unslash($_GET['gdb_message']))); ?></p>
        </div>
    <?php endif; ?>
    <?php // phpcs:enable WordPress.Security.NonceVerification.Recommended ?>

    <h2 class="nav-tab-wrapper">
        <?php foreach ($gdb_tabs as $tab_key => $tab_label) : ?>
            <a href="<?php echo esc_url(add_query_arg(['page' => 'gdb-settings', 'tab' => $tab_key], admin_url('admin.php'))); ?>"
               class="nav-tab <?php echo ($gdb_active_tab === $tab_key) ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_label); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <div class="gdb-settings-tab-content" style="margin-top: 20px;">
        <?php
        $tab_file = GDB_PATH . 'admin/settings-tabs/tab-' . $gdb_active_tab . '.php';
        if (file_exists($tab_file)) {
            include $tab_file;
        }
        ?>
    </div>
</div>

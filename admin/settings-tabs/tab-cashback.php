<?php
if (!defined('ABSPATH')) {
    exit;
}

$topup_enabled = get_option('gdb_cashback_topup_enabled', '');
$topup_percent = get_option('gdb_cashback_topup_percent', 0);

$gold_enabled = get_option('gdb_cashback_gold_enabled', '');
$gold_percent = get_option('gdb_cashback_gold_percent', 0);

$order_enabled = get_option('gdb_cashback_order_enabled', '');
$order_percent = get_option('gdb_cashback_order_percent', 0);
?>
<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <?php wp_nonce_field('gdb_save_cashback_settings', 'gdb_cashback_nonce'); ?>
    <input type="hidden" name="action" value="gdb_save_cashback_settings">

    <h2><?php _e('کش‌بک شارژ کیف پول', 'golden-dashboard'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('فعال‌سازی', 'golden-dashboard'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="cashback_topup_enabled" value="1" <?php checked($topup_enabled, '1'); ?>>
                    <?php _e('بعد از هر شارژ موفق کیف پول، درصدی به‌عنوان هدیه به کیف پول کاربر برگردانده شود', 'golden-dashboard'); ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('درصد کش‌بک', 'golden-dashboard'); ?></th>
            <td><input type="number" name="cashback_topup_percent" value="<?php echo esc_attr($topup_percent); ?>" min="0" max="100" step="0.1"> %</td>
        </tr>
    </table>

    <h2><?php _e('کش‌بک خرید طلا', 'golden-dashboard'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('فعال‌سازی', 'golden-dashboard'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="cashback_gold_enabled" value="1" <?php checked($gold_enabled, '1'); ?>>
                    <?php _e('بعد از هر خرید موفق طلا/فلز، درصدی از مبلغ ریالی به کیف پول کاربر برگردانده شود', 'golden-dashboard'); ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('درصد کش‌بک', 'golden-dashboard'); ?></th>
            <td><input type="number" name="cashback_gold_percent" value="<?php echo esc_attr($gold_percent); ?>" min="0" max="100" step="0.1"> %</td>
        </tr>
    </table>

    <h2><?php _e('کش‌بک خرید عادی ووکامرس', 'golden-dashboard'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('فعال‌سازی', 'golden-dashboard'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="cashback_order_enabled" value="1" <?php checked($order_enabled, '1'); ?>>
                    <?php _e('بعد از هر خرید موفق (هر سفارش عادی تکمیل‌شده در فروشگاه)، درصدی از مبلغ سفارش به کیف پول کاربر برگردانده شود', 'golden-dashboard'); ?>
                </label>
                <p class="description"><?php _e('سفارش‌های داخلی افزونه (شارژ کیف پول، خرید طلا) از این بخش مستثنی هستند، چون هرکدام کش‌بک اختصاصی خودشان را دارند.', 'golden-dashboard'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('درصد کش‌بک', 'golden-dashboard'); ?></th>
            <td><input type="number" name="cashback_order_percent" value="<?php echo esc_attr($order_percent); ?>" min="0" max="100" step="0.1"> %</td>
        </tr>
    </table>

    <p class="submit">
        <button type="submit" class="button button-primary button-large"><?php _e('ذخیره تنظیمات', 'golden-dashboard'); ?></button>
    </p>
</form>

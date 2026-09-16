<?php
if (!defined('ABSPATH')) {
    exit;
}

$fee_percent = get_option('gdb_withdraw_fee_percent', 0);
$topup_product_id = (int) get_option('gdb_wallet_topup_product_id', 0);
$currency = function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : '';
?>
<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <?php wp_nonce_field('gdb_save_settings', 'gdb_settings_nonce'); ?>
    <input type="hidden" name="action" value="gdb_save_settings">
    <input type="hidden" name="gdb_settings_tab" value="wallet">

    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('واحد پول فعال ووکامرس', 'golden-dashboard'); ?></th>
            <td>
                <strong><?php echo esc_html($currency ?: '-'); ?></strong>
                <p class="description"><?php _e('این مقدار فقط اطلاعاتی است و از تنظیمات ووکامرس (فروشگاه ← تنظیمات ← عمومی) خوانده می‌شود. تمام مبالغ در سراسر افزونه به‌طور خودکار بر اساس همین واحد نمایش داده می‌شوند.', 'golden-dashboard'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('درصد کارمزد برداشت', 'golden-dashboard'); ?></th>
            <td>
                <input type="number" name="gdb_withdraw_fee_percent"
                       value="<?php echo esc_attr($fee_percent); ?>"
                       step="0.01" min="0" max="100">
                <p class="description"><?php _e('درصدی که از مبلغ درخواستی برداشت به عنوان کارمزد کسر می‌شود.', 'golden-dashboard'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('محصول ووکامرس شارژ کیف پول', 'golden-dashboard'); ?></th>
            <td>
                <?php if (function_exists('wc_get_products')) :
                    $products = wc_get_products(['limit' => 200, 'status' => 'publish', 'orderby' => 'title', 'order' => 'ASC']);
                ?>
                    <select name="gdb_wallet_topup_product_id">
                        <option value="0" <?php selected($topup_product_id, 0); ?>><?php _e('خودکار (ساخته‌شده توسط خود افزونه)', 'golden-dashboard'); ?></option>
                        <?php foreach ($products as $product) : ?>
                            <option value="<?php echo esc_attr($product->get_id()); ?>" <?php selected($topup_product_id, $product->get_id()); ?>>
                                <?php echo esc_html($product->get_name()); ?> (#<?php echo esc_html($product->get_id()); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($topup_product_id) : ?>
                        <a href="<?php echo esc_url(get_edit_post_link($topup_product_id)); ?>" class="button" target="_blank"><?php _e('ویرایش محصول', 'golden-dashboard'); ?></a>
                    <?php endif; ?>
                    <p class="description"><?php _e('محصول ووکامرسی که برای ثبت سفارش‌های شارژ کیف پول استفاده می‌شود. در حالت «خودکار»، افزونه در اولین شارژ یک محصول مخفی می‌سازد و خودش مدیریت می‌کند.', 'golden-dashboard'); ?></p>
                <?php else : ?>
                    <p class="description"><?php _e('ووکامرس در حال حاضر فعال نیست.', 'golden-dashboard'); ?></p>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <?php submit_button(__('ذخیره تنظیمات', 'golden-dashboard')); ?>
</form>

<?php
if (!defined('ABSPATH')) {
    exit;
}

$edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
$edit_type = $edit_id ? GDB_Gold_Wallet::get_type($edit_id) : null;
$types = GDB_Gold_Wallet::get_types();

$products = function_exists('wc_get_products') ? wc_get_products(['limit' => 200, 'status' => 'publish', 'orderby' => 'title', 'order' => 'ASC']) : [];
?>
<h2><?php echo $edit_type ? __('ویرایش نوع کیف پول طلا', 'golden-dashboard') : __('افزودن نوع جدید', 'golden-dashboard'); ?></h2>

<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <?php wp_nonce_field('gdb_save_gold_type', 'gdb_gold_type_nonce'); ?>
    <input type="hidden" name="action" value="gdb_save_gold_type">
    <input type="hidden" name="id" value="<?php echo esc_attr($edit_id); ?>">

    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('نام', 'golden-dashboard'); ?></th>
            <td><input type="text" name="name" class="regular-text" required value="<?php echo esc_attr($edit_type->name ?? ''); ?>" placeholder="مثلاً: طلای ۱۸ عیار"></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('واحد اندازه‌گیری', 'golden-dashboard'); ?></th>
            <td><input type="text" name="unit_label" class="small-text" value="<?php echo esc_attr($edit_type->unit_label ?? 'گرم'); ?>" placeholder="گرم">
                <p class="description"><?php _e('مثلاً «گرم» یا «مثقال»', 'golden-dashboard'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('محصول ووکامرس مرجع قیمت', 'golden-dashboard'); ?></th>
            <td>
                <?php if ($products) : ?>
                    <select name="wc_product_id" required>
                        <option value=""><?php _e('انتخاب کنید...', 'golden-dashboard'); ?></option>
                        <?php foreach ($products as $product) : ?>
                            <option value="<?php echo esc_attr($product->get_id()); ?>" <?php selected($edit_type->wc_product_id ?? 0, $product->get_id()); ?>>
                                <?php echo esc_html($product->get_name()); ?> (#<?php echo esc_html($product->get_id()); ?>) - <?php echo wp_kses_post($product->get_price_html()); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('قیمت این محصول (که توسط افزونه‌ی نرخ به‌روزرسانی می‌شود) به‌عنوان قیمت هر واحد استفاده می‌شود.', 'golden-dashboard'); ?></p>
                <?php else : ?>
                    <p class="description"><?php _e('محصولی در ووکامرس یافت نشد.', 'golden-dashboard'); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('مبالغ پیشنهادی', 'golden-dashboard'); ?></th>
            <td>
                <input type="text" name="suggested_amounts" class="regular-text" value="<?php echo esc_attr($edit_type->suggested_amounts ?? ''); ?>" placeholder="1, 5, 10, 20">
                <p class="description"><?php _e('چند عدد جدا شده با ویرگول، به همان واحد اندازه‌گیری (مثلاً گرم)', 'golden-dashboard'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('حداقل مقدار خرید', 'golden-dashboard'); ?></th>
            <td><input type="number" name="min_amount" step="0.001" min="0" value="<?php echo esc_attr($edit_type->min_amount ?? 0); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('حداکثر مقدار خرید', 'golden-dashboard'); ?></th>
            <td><input type="number" name="max_amount" step="0.001" min="0" value="<?php echo esc_attr($edit_type->max_amount ?? 0); ?>">
                <p class="description"><?php _e('صفر یعنی بدون محدودیت', 'golden-dashboard'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('گام افزایش', 'golden-dashboard'); ?></th>
            <td><input type="number" name="step_amount" step="0.001" min="0.001" value="<?php echo esc_attr($edit_type->step_amount ?? 0.1); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('روش پرداخت', 'golden-dashboard'); ?></th>
            <td>
                <?php $payment_mode = $edit_type->payment_mode ?? 'wallet_only'; ?>
                <label>
                    <input type="radio" name="payment_mode" value="wallet_only" <?php checked($payment_mode, 'wallet_only'); ?>>
                    <?php _e('فقط از موجودی کیف پول (اگر موجودی کافی نبود، پیام شارژ کنید نمایش داده شود)', 'golden-dashboard'); ?>
                </label><br>
                <label>
                    <input type="radio" name="payment_mode" value="wallet_or_gateway" <?php checked($payment_mode, 'wallet_or_gateway'); ?>>
                    <?php _e('کیف پول یا درگاه مستقیم (اگر موجودی کم بود، مستقیم به درگاه پرداخت هدایت شود)', 'golden-dashboard'); ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('وضعیت', 'golden-dashboard'); ?></th>
            <td>
                <label><input type="radio" name="status" value="active" <?php checked($edit_type->status ?? 'active', 'active'); ?>> <?php _e('فعال', 'golden-dashboard'); ?></label>
                <label><input type="radio" name="status" value="inactive" <?php checked($edit_type->status ?? '', 'inactive'); ?>> <?php _e('غیرفعال', 'golden-dashboard'); ?></label>
            </td>
        </tr>
    </table>

    <p class="submit">
        <button type="submit" class="button button-primary"><?php echo $edit_type ? __('به‌روزرسانی', 'golden-dashboard') : __('افزودن', 'golden-dashboard'); ?></button>
        <?php if ($edit_type) : ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-settings&tab=gold-wallet')); ?>" class="button"><?php _e('انصراف', 'golden-dashboard'); ?></a>
        <?php endif; ?>
    </p>
</form>

<hr>

<h2><?php _e('انواع تعریف‌شده', 'golden-dashboard'); ?></h2>

<?php if (empty($types)) : ?>
    <p><?php _e('هنوز هیچ نوعی تعریف نشده است.', 'golden-dashboard'); ?></p>
<?php else : ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('نام', 'golden-dashboard'); ?></th>
                <th><?php _e('واحد', 'golden-dashboard'); ?></th>
                <th><?php _e('محصول مرجع', 'golden-dashboard'); ?></th>
                <th><?php _e('قیمت لحظه‌ای', 'golden-dashboard'); ?></th>
                <th><?php _e('روش پرداخت', 'golden-dashboard'); ?></th>
                <th><?php _e('وضعیت', 'golden-dashboard'); ?></th>
                <th><?php _e('عملیات', 'golden-dashboard'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($types as $type) :
                $product = function_exists('wc_get_product') ? wc_get_product($type->wc_product_id) : null;
                $price = $product ? (float) $product->get_price() : 0;
            ?>
                <tr>
                    <td><?php echo esc_html($type->name); ?></td>
                    <td><?php echo esc_html($type->unit_label); ?></td>
                    <td><?php echo $product ? esc_html($product->get_name()) . ' (#' . $type->wc_product_id . ')' : __('یافت نشد', 'golden-dashboard'); ?></td>
                    <td><?php echo $price ? wp_kses_post(wc_price($price)) : '-'; ?></td>
                    <td><?php echo $type->payment_mode === 'wallet_or_gateway' ? __('کیف پول یا درگاه', 'golden-dashboard') : __('فقط کیف پول', 'golden-dashboard'); ?></td>
                    <td><?php echo $type->status === 'active' ? '<span style="color:#16a34a;">' . __('فعال', 'golden-dashboard') . '</span>' : '<span style="color:#9ca3af;">' . __('غیرفعال', 'golden-dashboard') . '</span>'; ?></td>
                    <td>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-settings&tab=gold-wallet&edit=' . $type->id)); ?>" class="button button-small"><?php _e('ویرایش', 'golden-dashboard'); ?></a>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                            <?php wp_nonce_field('gdb_delete_gold_type', 'gdb_delete_gold_type_nonce'); ?>
                            <input type="hidden" name="action" value="gdb_delete_gold_type">
                            <input type="hidden" name="id" value="<?php echo esc_attr($type->id); ?>">
                            <button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e('این نوع حذف شود؟ موجودی کاربران در جدول باقی می‌ماند اما دیگر قابل خرید نخواهد بود.', 'golden-dashboard'); ?>');"><?php _e('حذف', 'golden-dashboard'); ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

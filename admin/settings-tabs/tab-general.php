<?php
if (!defined('ABSPATH')) {
    exit;
}

$enable_admin_email = get_option('gdb_enable_admin_email', 'yes');
$enable_user_email = get_option('gdb_enable_user_email', 'yes');
?>
<form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
    <?php wp_nonce_field('gdb_save_settings', 'gdb_settings_nonce'); ?>
    <input type="hidden" name="action" value="gdb_save_settings">
    <input type="hidden" name="gdb_settings_tab" value="general">

    <table class="form-table">
        <tr>
            <th scope="row"><?php _e('ارسال ایمیل به مدیر', 'golden-dashboard'); ?></th>
            <td>
                <label><input type="radio" name="gdb_enable_admin_email" value="yes" <?php checked($enable_admin_email, 'yes'); ?>> <?php _e('فعال', 'golden-dashboard'); ?></label>
                <label><input type="radio" name="gdb_enable_admin_email" value="no" <?php checked($enable_admin_email, 'no'); ?>> <?php _e('غیرفعال', 'golden-dashboard'); ?></label>
                <p class="description"><?php _e('در صورت فعال بودن، هنگام ثبت درخواست برداشت، ایمیلی به مدیر سایت ارسال می‌شود.', 'golden-dashboard'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('ارسال ایمیل به کاربر', 'golden-dashboard'); ?></th>
            <td>
                <label><input type="radio" name="gdb_enable_user_email" value="yes" <?php checked($enable_user_email, 'yes'); ?>> <?php _e('فعال', 'golden-dashboard'); ?></label>
                <label><input type="radio" name="gdb_enable_user_email" value="no" <?php checked($enable_user_email, 'no'); ?>> <?php _e('غیرفعال', 'golden-dashboard'); ?></label>
                <p class="description"><?php _e('در صورت فعال بودن، هنگام تایید یا رد درخواست برداشت، ایمیلی به کاربر ارسال می‌شود.', 'golden-dashboard'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('حذف جداول هنگام حذف افزونه', 'golden-dashboard'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="gdb_uninstall_delete_tables" value="1" <?php checked(get_option('gdb_uninstall_delete_tables', false), true); ?>>
                    <?php _e('در صورت فعال بودن، هنگام حذف افزونه تمام جداول و تنظیمات مرتبط با کیف پول حذف می‌شوند.', 'golden-dashboard'); ?>
                </label>
            </td>
        </tr>
    </table>

    <?php submit_button(__('ذخیره تنظیمات', 'golden-dashboard')); ?>
</form>

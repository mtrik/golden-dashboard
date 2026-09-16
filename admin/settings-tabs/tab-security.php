<?php
if (!defined('ABSPATH')) {
    exit;
}

$rate_limit_enabled          = get_option('gdb_security_rate_limit_enabled', '');
$rate_limit_window           = get_option('gdb_security_rate_limit_window', 60);
$rate_limit_max_attempts     = get_option('gdb_security_rate_limit_max_attempts', 5);
$rate_limit_block_duration   = get_option('gdb_security_rate_limit_block_duration', 300);
$suspicious_amount_threshold = get_option('gdb_security_suspicious_amount_threshold', 0);
$require_verification_above  = get_option('gdb_security_require_verification_above', 0);
$alert_admin_on_suspicious    = get_option('gdb_security_alert_admin_on_suspicious', '1');
$max_daily_transactions       = get_option('gdb_security_max_daily_transactions', 0);
$max_transaction_amount       = get_option('gdb_security_max_transaction_amount', 0);
$log_all_transactions         = get_option('gdb_security_log_all_transactions', '1');
?>
<div class="gdb-security-settings">
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('gdb_save_security_settings', 'gdb_security_nonce'); ?>
        <input type="hidden" name="action" value="gdb_save_security_settings">

        <h2><?php _e('تنظیمات امنیتی', 'golden-dashboard'); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row" colspan="2"><h3 style="margin:0;"><?php _e('محدودسازی نرخ درخواست (Rate Limiting)', 'golden-dashboard'); ?></h3></th>
            </tr>
            <tr>
                <th scope="row"><label for="rate_limit_enabled"><?php _e('فعال‌سازی محدودسازی نرخ', 'golden-dashboard'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="rate_limit_enabled" id="rate_limit_enabled" value="1" <?php checked($rate_limit_enabled, '1'); ?>>
                        <?php _e('محدود کردن تعداد درخواست‌های کاربران', 'golden-dashboard'); ?>
                    </label>
                    <p class="description"><?php _e('جلوگیری از حملات brute force و استفاده بیش از حد', 'golden-dashboard'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="rate_limit_window"><?php _e('بازه زمانی (ثانیه)', 'golden-dashboard'); ?></label></th>
                <td>
                    <input type="number" name="rate_limit_window" id="rate_limit_window" value="<?php echo esc_attr($rate_limit_window); ?>" min="1" class="small-text">
                    <p class="description"><?php _e('بازه زمانی برای شمارش تلاش‌ها (پیشنهاد: 60 ثانیه)', 'golden-dashboard'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="rate_limit_max_attempts"><?php _e('حداکثر تلاش', 'golden-dashboard'); ?></label></th>
                <td>
                    <input type="number" name="rate_limit_max_attempts" id="rate_limit_max_attempts" value="<?php echo esc_attr($rate_limit_max_attempts); ?>" min="1" class="small-text">
                    <p class="description"><?php _e('حداکثر تعداد تلاش در بازه زمانی (پیشنهاد: 5)', 'golden-dashboard'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="rate_limit_block_duration"><?php _e('مدت زمان مسدودسازی (ثانیه)', 'golden-dashboard'); ?></label></th>
                <td>
                    <input type="number" name="rate_limit_block_duration" id="rate_limit_block_duration" value="<?php echo esc_attr($rate_limit_block_duration); ?>" min="1" class="small-text">
                    <p class="description"><?php _e('مدت زمان مسدود بودن پس از تلاش بیش از حد (پیشنهاد: 300 ثانیه = 5 دقیقه)', 'golden-dashboard'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row" colspan="2"><h3 style="margin:20px 0 0 0;"><?php _e('شناسایی تراکنش‌های مشکوک', 'golden-dashboard'); ?></h3></th>
            </tr>
            <tr>
                <th scope="row"><label for="suspicious_amount_threshold"><?php _e('آستانه مبلغ مشکوک', 'golden-dashboard'); ?></label></th>
                <td>
                    <input type="number" name="suspicious_amount_threshold" id="suspicious_amount_threshold" value="<?php echo esc_attr($suspicious_amount_threshold); ?>" min="0" step="10000" class="regular-text">
                    <p class="description">
                        <?php _e('تراکنش‌های بالاتر از این مبلغ به‌عنوان مشکوک علامت‌گذاری می‌شوند. صفر یعنی غیرفعال.', 'golden-dashboard'); ?>
                        <?php if ($suspicious_amount_threshold > 0) : ?>
                            <br><?php printf(__('معادل نمایشی فعلی: %s', 'golden-dashboard'), gdb_price($suspicious_amount_threshold)); ?>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="require_verification_above"><?php _e('نیاز به تایید برای مبلغ بالاتر از', 'golden-dashboard'); ?></label></th>
                <td>
                    <input type="number" name="require_verification_above" id="require_verification_above" value="<?php echo esc_attr($require_verification_above); ?>" min="0" step="10000" class="regular-text">
                    <p class="description">
                        <?php _e('تراکنش‌های بالاتر از این مبلغ به‌عنوان «نیازمند بررسی مدیر» علامت‌گذاری می‌شوند (فهرست آن‌ها در لاگ امنیتی قابل مشاهده است). صفر یعنی غیرفعال.', 'golden-dashboard'); ?>
                        <?php if ($require_verification_above > 0) : ?>
                            <br><?php printf(__('معادل نمایشی فعلی: %s', 'golden-dashboard'), gdb_price($require_verification_above)); ?>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="alert_admin_on_suspicious"><?php _e('اطلاع‌رسانی به مدیر', 'golden-dashboard'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="alert_admin_on_suspicious" id="alert_admin_on_suspicious" value="1" <?php checked($alert_admin_on_suspicious, '1'); ?>>
                        <?php _e('ارسال ایمیل به مدیر هنگام شناسایی فعالیت مشکوک', 'golden-dashboard'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th scope="row" colspan="2"><h3 style="margin:20px 0 0 0;"><?php _e('محدودیت‌های تراکنش', 'golden-dashboard'); ?></h3></th>
            </tr>
            <tr>
                <th scope="row"><label for="max_daily_transactions"><?php _e('حداکثر تراکنش روزانه', 'golden-dashboard'); ?></label></th>
                <td>
                    <input type="number" name="max_daily_transactions" id="max_daily_transactions" value="<?php echo esc_attr($max_daily_transactions); ?>" min="0" class="small-text">
                    <p class="description"><?php _e('حداکثر تعداد تراکنش (شارژ یا برداشت) در روز برای هر کاربر. صفر یعنی بدون محدودیت.', 'golden-dashboard'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="max_transaction_amount"><?php _e('حداکثر مبلغ تراکنش', 'golden-dashboard'); ?></label></th>
                <td>
                    <input type="number" name="max_transaction_amount" id="max_transaction_amount" value="<?php echo esc_attr($max_transaction_amount); ?>" min="0" step="10000" class="regular-text">
                    <p class="description">
                        <?php _e('حداکثر مبلغ مجاز برای هر تراکنش شارژ یا برداشت. صفر یعنی بدون محدودیت.', 'golden-dashboard'); ?>
                        <?php if ($max_transaction_amount > 0) : ?>
                            <br><?php printf(__('معادل نمایشی فعلی: %s', 'golden-dashboard'), gdb_price($max_transaction_amount)); ?>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row" colspan="2"><h3 style="margin:20px 0 0 0;"><?php _e('گزارش‌گیری', 'golden-dashboard'); ?></h3></th>
            </tr>
            <tr>
                <th scope="row"><label for="log_all_transactions"><?php _e('ثبت تمام تراکنش‌ها', 'golden-dashboard'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="log_all_transactions" id="log_all_transactions" value="1" <?php checked($log_all_transactions, '1'); ?>>
                        <?php _e('ثبت تمام تراکنش‌ها در لاگ امنیتی (حتی تراکنش‌های عادی)', 'golden-dashboard'); ?>
                    </label>
                    <p class="description"><?php _e('توجه: فعال‌سازی این گزینه باعث افزایش حجم دیتابیس می‌شود. رویدادهای واقعاً امنیتی (مسدودسازی، تراکنش مشکوک و...) صرف‌نظر از این تنظیم همیشه ثبت می‌شوند.', 'golden-dashboard'); ?></p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary button-large"><?php _e('ذخیره تنظیمات', 'golden-dashboard'); ?></button>
        </p>
    </form>
</div>

<div class="gdb-blocked-ips" style="margin-top:40px;">
    <h2><?php _e('مدیریت IP های مسدود شده', 'golden-dashboard'); ?></h2>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-bottom:20px;">
        <?php wp_nonce_field('gdb_block_ip', 'gdb_block_ip_nonce'); ?>
        <input type="hidden" name="action" value="gdb_block_ip">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="ip_address"><?php _e('مسدود کردن IP', 'golden-dashboard'); ?></label></th>
                <td>
                    <input type="text" name="ip_address" id="ip_address" class="regular-text" placeholder="192.168.1.1" required>
                    <input type="text" name="reason" class="regular-text" placeholder="<?php esc_attr_e('دلیل (اختیاری)', 'golden-dashboard'); ?>">
                    <button type="submit" class="button"><?php _e('مسدود کردن', 'golden-dashboard'); ?></button>
                    <p class="description"><?php _e('IP آدرسی که می‌خواهید مسدود کنید را وارد کنید', 'golden-dashboard'); ?></p>
                </td>
            </tr>
        </table>
    </form>

    <?php
    $blocked_ips = class_exists('GDB_Security') ? GDB_Security::get_blocked_ips() : [];
    if (empty($blocked_ips)) :
    ?>
        <p><?php _e('هیچ IP مسدود شده‌ای وجود ندارد.', 'golden-dashboard'); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('IP', 'golden-dashboard'); ?></th>
                    <th><?php _e('دلیل', 'golden-dashboard'); ?></th>
                    <th><?php _e('تاریخ مسدودسازی', 'golden-dashboard'); ?></th>
                    <th><?php _e('عملیات', 'golden-dashboard'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blocked_ips as $row) : ?>
                    <tr>
                        <td><code><?php echo esc_html($row->ip_address); ?></code></td>
                        <td><?php echo esc_html($row->reason ?: '-'); ?></td>
                        <td><?php echo esc_html($row->created_at); ?></td>
                        <td>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                                <?php wp_nonce_field('gdb_unblock_ip', 'gdb_unblock_ip_nonce'); ?>
                                <input type="hidden" name="action" value="gdb_unblock_ip">
                                <input type="hidden" name="id" value="<?php echo esc_attr($row->id); ?>">
                                <button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e('آیا مطمئن هستید؟', 'golden-dashboard'); ?>');"><?php _e('رفع مسدودی', 'golden-dashboard'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="gdb-security-stats" style="margin-top:40px;">
    <h2><?php _e('آمار امنیتی', 'golden-dashboard'); ?></h2>

    <?php $stats = class_exists('GDB_Security') ? GDB_Security::get_stats_24h() : ['critical' => 0, 'high' => 0, 'total' => 0]; ?>

    <div class="gdb-security-cards" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:20px;">
        <div class="card" style="padding:20px; background:#fff; border:1px solid #ddd; border-radius:5px; border-right:4px solid #dc3545;">
            <h3 style="margin:0 0 10px 0; color:#dc3545;"><?php echo esc_html(number_format_i18n($stats['critical'])); ?></h3>
            <p style="margin:0; color:#666;"><?php _e('رویدادهای بحرانی (۲۴ ساعت)', 'golden-dashboard'); ?></p>
        </div>
        <div class="card" style="padding:20px; background:#fff; border:1px solid #ddd; border-radius:5px; border-right:4px solid #ff9800;">
            <h3 style="margin:0 0 10px 0; color:#ff9800;"><?php echo esc_html(number_format_i18n($stats['high'])); ?></h3>
            <p style="margin:0; color:#666;"><?php _e('رویدادهای خطرناک (۲۴ ساعت)', 'golden-dashboard'); ?></p>
        </div>
        <div class="card" style="padding:20px; background:#fff; border:1px solid #ddd; border-radius:5px; border-right:4px solid #0073aa;">
            <h3 style="margin:0 0 10px 0; color:#0073aa;"><?php echo esc_html(number_format_i18n($stats['total'])); ?></h3>
            <p style="margin:0; color:#666;"><?php _e('کل رویدادها (۲۴ ساعت)', 'golden-dashboard'); ?></p>
        </div>
    </div>

    <p style="margin-top:20px;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-security-log')); ?>" class="button">
            <?php _e('مشاهده لاگ کامل امنیتی', 'golden-dashboard'); ?>
        </a>
    </p>
</div>

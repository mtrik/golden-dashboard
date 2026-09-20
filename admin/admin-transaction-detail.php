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

if (!current_user_can('manage_options')) {
    wp_die(esc_html__('شما اجازه دسترسی به این صفحه را ندارید.', 'golden-dashboard'));
}

$transaction_id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0;
if (!$transaction_id) {
    wp_die(esc_html__('شناسه تراکنش نامعتبر است.', 'golden-dashboard'));
}

global $wpdb;
$table = $wpdb->prefix . 'gd_wallet_transactions';
$transaction = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $transaction_id));

if (!$transaction) {
    wp_die(esc_html__('تراکنش یافت نشد.', 'golden-dashboard'));
}

$user = get_userdata($transaction->user_id);
$meta_data = maybe_unserialize($transaction->meta_data);
$is_pending = ($transaction->status === 'pending' && $transaction->transaction_type === 'withdraw_request');

if ($is_pending && isset($_POST['gdb_detail_action'])) {
    check_admin_referer('gdb_detail_action', 'gdb_detail_nonce');
    
    if (sanitize_key(wp_unslash($_POST['gdb_detail_action'])) === 'approve') {
        $payment_data = [
            'bank_transaction_id' => sanitize_text_field((isset($_POST['bank_transaction_id']) ? wp_unslash($_POST['bank_transaction_id']) : '')),
            'admin_note'          => sanitize_textarea_field((isset($_POST['admin_note']) ? wp_unslash($_POST['admin_note']) : '')),
            'bank_date'           => gdb_normalize_admin_date_input(sanitize_text_field((isset($_POST['bank_date']) ? wp_unslash($_POST['bank_date']) : ''))),
        ];
        $result = GDB_Withdraw_Request::approve($transaction_id, $payment_data);
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            GDB_Admin::save_audit_log($transaction_id, 'approved', get_current_user_id(), $payment_data['admin_note']);
            echo '<div class="notice notice-success"><p>' . esc_html__('درخواست با موفقیت تایید شد.', 'golden-dashboard') . '</p></div>';
            echo '<meta http-equiv="refresh" content="2;url=' . esc_url(admin_url('admin.php?page=gdb-transaction-detail&id=' . $transaction_id)) . '">';
        }
    } elseif (sanitize_key(wp_unslash($_POST['gdb_detail_action'])) === 'reject') {
        $reason = sanitize_textarea_field((isset($_POST['reason']) ? wp_unslash($_POST['reason']) : ''));
        $result = GDB_Withdraw_Request::reject($transaction_id, $reason);
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            GDB_Admin::save_audit_log($transaction_id, 'rejected', get_current_user_id(), $reason);
            echo '<div class="notice notice-success"><p>' . esc_html__('درخواست با موفقیت رد شد.', 'golden-dashboard') . '</p></div>';
            echo '<meta http-equiv="refresh" content="2;url=' . esc_url(admin_url('admin.php?page=gdb-transaction-detail&id=' . $transaction_id)) . '">';
        }
    }
    
    $transaction = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $transaction_id));
    $is_pending = ($transaction->status === 'pending' && $transaction->transaction_type === 'withdraw_request');
}

$audit_log = GDB_Admin::get_audit_log($transaction_id);

?>
<div class="wrap">
    <h1><?php
    /* translators: %d: transaction ID */
    echo esc_html(sprintf(__('جزئیات تراکنش #%d', 'golden-dashboard'), $transaction_id));
    ?></h1>
    
    <div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-withdraw-requests')); ?>" class="button">&larr; <?php esc_html_e('بازگشت به لیست درخواست‌ها', 'golden-dashboard'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-transactions-history')); ?>" class="button">&larr; <?php esc_html_e('بازگشت به تاریخچه تراکنش‌ها', 'golden-dashboard'); ?></a>
        <button type="button" class="button button-primary" onclick="window.print();" style="margin-right:auto;">
            <span class="dashicons dashicons-printer" style="margin-top:4px;"></span> <?php esc_html_e('چاپ رسید', 'golden-dashboard'); ?>
        </button>
    </div>

    <?php if ($is_pending) : ?>
        <div class="notice notice-warning">
            <p><strong><?php esc_html_e('این درخواست در انتظار بررسی است.', 'golden-dashboard'); ?></strong></p>
        </div>
    <?php endif; ?>

    <div class="gdb-detail-card gdb-print-card">
        <h2 class="gdb-detail-card-title"><?php esc_html_e('اطلاعات کاربر', 'golden-dashboard'); ?></h2>
        <div class="gdb-detail-card-body">
            <table class="gdb-detail-table">
                <tr>
                    <th><?php esc_html_e('نام کاربری', 'golden-dashboard'); ?></th>
                    <td>
                        <?php if ($user) : ?>
                            <strong><?php echo esc_html($user->display_name); ?></strong>
                            <br>
                            <?php echo esc_html($user->user_email); ?>
                            <br>
                            <?php echo esc_html__('شناسه کاربری:', 'golden-dashboard'); ?> <?php echo absint($user->ID); ?>
                            <br>
                            <a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>" target="_blank"><?php esc_html_e('ویرایش کاربر', 'golden-dashboard'); ?></a>
                        <?php else : ?>
                            <?php esc_html_e('کاربر حذف شده است', 'golden-dashboard'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="gdb-detail-card gdb-print-card">
        <h2 class="gdb-detail-card-title"><?php esc_html_e('اطلاعات تراکنش', 'golden-dashboard'); ?></h2>
        <div class="gdb-detail-card-body">
            <table class="gdb-detail-table">
                <tr><th><?php esc_html_e('شناسه تراکنش', 'golden-dashboard'); ?></th><td><strong>#<?php echo absint($transaction->id); ?></strong></td></tr>
                <tr><th><?php esc_html_e('نوع تراکنش', 'golden-dashboard'); ?></th><td><?php echo esc_html($transaction->transaction_type); ?></td></tr>
                <tr>
                    <th><?php esc_html_e('نوع (واریز/برداشت)', 'golden-dashboard'); ?></th>
                    <td>
                        <?php if ($transaction->type === 'credit') : ?>
                            <span style="color:green;"><?php esc_html_e('واریز', 'golden-dashboard'); ?></span>
                        <?php else : ?>
                            <span style="color:red;"><?php esc_html_e('برداشت', 'golden-dashboard'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr><th><?php esc_html_e('مبلغ درخواستی', 'golden-dashboard'); ?></th><td><strong><?php echo wp_kses_post(gdb_price($transaction->amount)); ?></strong></td></tr>
                <?php if (isset($transaction->fee_amount) && $transaction->fee_amount > 0) : ?>
                    <tr>
                        <th><?php esc_html_e('کارمزد', 'golden-dashboard'); ?></th>
                        <td>
                            <span style="color:#dc2626;"><?php echo wp_kses_post(gdb_price($transaction->fee_amount)); ?></span>
                            <?php
                            $fee_percent = 0;
                            if ($transaction->amount > 0) {
                                $fee_percent = ($transaction->fee_amount / $transaction->amount) * 100;
                            }
                            ?>
                            <small>(<?php echo esc_html(number_format($fee_percent, 2)); ?>%)</small>
                        </td>
                    </tr>
                    <tr><th><?php esc_html_e('مبلغ قابل واریز', 'golden-dashboard'); ?></th><td><strong style="color:#16a34a;"><?php echo wp_kses_post(gdb_price($transaction->net_amount)); ?></strong></td></tr>
                <?php endif; ?>
                <tr><th><?php esc_html_e('موجودی قبل', 'golden-dashboard'); ?></th><td><?php echo wp_kses_post(gdb_price($transaction->balance_before)); ?></td></tr>
                <tr><th><?php esc_html_e('موجودی بعد', 'golden-dashboard'); ?></th><td><strong><?php echo wp_kses_post(gdb_price($transaction->balance_after)); ?></strong></td></tr>
                <tr>
                    <th><?php esc_html_e('وضعیت', 'golden-dashboard'); ?></th>
                    <td>
                        <?php
                        $status_labels = [
                            'pending'    => __('در انتظار پرداخت', 'golden-dashboard'),
                            'processing' => __('در حال پردازش', 'golden-dashboard'),
                            'completed'  => __('تکمیل شده', 'golden-dashboard'),
                            'on-hold'    => __('در انتظار', 'golden-dashboard'),
                            'cancelled'  => __('لغو شده', 'golden-dashboard'),
                            'refunded'   => __('بازگشت وجه', 'golden-dashboard'),
                            'failed'     => __('ناموفق', 'golden-dashboard'),
                            'rejected'   => __('رد شده', 'golden-dashboard'),
                        ];
                        $status_class = isset($transaction->status) ? $transaction->status : 'completed';
                        $status_text = isset($status_labels[$status_class]) ? $status_labels[$status_class] : $status_class;
                        ?>
                        <span class="gdb-status-badge gdb-status-<?php echo esc_attr($status_class); ?>">
                            <?php echo esc_html($status_text); ?>
                        </span>
                    </td>
                </tr>
                <tr><th><?php esc_html_e('کد پیگیری', 'golden-dashboard'); ?></th><td><code><?php echo esc_html($transaction->tracking_code); ?></code></td></tr>
                <tr><th><?php esc_html_e('تاریخ ایجاد', 'golden-dashboard'); ?></th><td><?php echo esc_html(gdb_date_jalali($transaction->created_at, true)); ?></td></tr>
                <tr><th><?php esc_html_e('تاریخ به‌روزرسانی', 'golden-dashboard'); ?></th><td><?php echo esc_html(gdb_date_jalali($transaction->updated_at, true)); ?></td></tr>
                <tr><th><?php esc_html_e('توضیحات', 'golden-dashboard'); ?></th><td><?php echo esc_html($transaction->description); ?></td></tr>
            </table>
        </div>
    </div>

    <div class="gdb-detail-card gdb-print-card">
        <h2 class="gdb-detail-card-title"><?php esc_html_e('اطلاعات بانکی و یادداشت‌ها', 'golden-dashboard'); ?></h2>
        <div class="gdb-detail-card-body">
            <table class="gdb-detail-table">
                <tr>
                    <th><label for="bank_transaction_id"><?php esc_html_e('شماره تراکنش بانکی', 'golden-dashboard'); ?></label></th>
                    <td>
                        <?php if ($is_pending) : ?>
                            <input type="text" name="bank_transaction_id" id="bank_transaction_id" class="widefat" value="<?php echo esc_attr($transaction->bank_transaction_id); ?>" disabled>
                            <p class="description"><?php esc_html_e('پس از تایید درخواست قابل ویرایش است.', 'golden-dashboard'); ?></p>
                        <?php else : ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <?php wp_nonce_field('gdb_update_transaction', 'gdb_update_nonce'); ?>
                                <input type="hidden" name="action" value="gdb_update_transaction">
                                <input type="hidden" name="transaction_id" value="<?php echo absint($transaction_id); ?>">
                                <input type="text" name="bank_transaction_id" class="widefat" value="<?php echo esc_attr($transaction->bank_transaction_id); ?>" style="margin-bottom:8px;">
                                <input type="text" class="widefat date-picker" autocomplete="off" name="bank_date" value="<?php echo esc_attr(gdb_display_admin_date_input($transaction->bank_date)); ?>" style="margin-bottom:8px;">
                                <textarea name="admin_note" class="widefat" rows="3" style="margin-bottom:8px;"><?php echo esc_textarea($transaction->admin_note); ?></textarea>
                                <button type="submit" class="button button-primary"><?php esc_html_e('ذخیره تغییرات', 'golden-dashboard'); ?></button>
                            </form>
                            <p class="description"><?php esc_html_e('ویرایش شماره تراکنش، تاریخ واریز و یادداشت مدیر.', 'golden-dashboard'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($transaction->admin_note)) : ?>
                    <tr><th><?php esc_html_e('یادداشت مدیر', 'golden-dashboard'); ?></th><td><?php echo esc_html($transaction->admin_note); ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($transaction->bank_transaction_id)) : ?>
                    <tr><th><?php esc_html_e('شماره تراکنش بانکی', 'golden-dashboard'); ?></th><td><?php echo esc_html($transaction->bank_transaction_id); ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($transaction->bank_date)) : ?>
                    <tr><th><?php esc_html_e('تاریخ واریز بانکی', 'golden-dashboard'); ?></th><td><?php echo esc_html(gdb_date_jalali($transaction->bank_date, false)); ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div class="gdb-detail-card gdb-no-print">
        <h2 class="gdb-detail-card-title"><?php esc_html_e('اطلاعات امنیتی', 'golden-dashboard'); ?></h2>
        <div class="gdb-detail-card-body">
            <table class="gdb-detail-table">
                <tr><th><?php esc_html_e('آیپی', 'golden-dashboard'); ?></th><td><?php echo esc_html($transaction->ip_address); ?></td></tr>
                <tr><th><?php esc_html_e('مرورگر', 'golden-dashboard'); ?></th><td><?php echo esc_html($transaction->user_agent); ?></td></tr>
                <tr><th><?php esc_html_e('شناسه مرجع', 'golden-dashboard'); ?></th><td><?php echo $transaction->reference_id ? '#' . absint($transaction->reference_id) : '-'; ?></td></tr>
            </table>
        </div>
    </div>

    <?php if ($meta_data && is_array($meta_data)) : ?>
        <div class="gdb-detail-card gdb-no-print">
            <h2 class="gdb-detail-card-title"><?php esc_html_e('داده‌های اضافی', 'golden-dashboard'); ?></h2>
            <div class="gdb-detail-card-body">
                <table class="gdb-detail-table">
                    <?php 
                    $translated_keys = [
                        'fee_percent' => __('درصد کارمزد', 'golden-dashboard'),
                        'fee_amount' => __('مبلغ کارمزد', 'golden-dashboard'),
                        'net_amount' => __('مبلغ قابل واریز', 'golden-dashboard'),
                        'original_amount' => __('مبلغ درخواستی اصلی', 'golden-dashboard'),
                        'order_id' => __('شناسه سفارش', 'golden-dashboard'),
                        'is_topup' => __('شارژ کیف پول', 'golden-dashboard'),
                        'context' => __('مربوط به', 'golden-dashboard'),
                        'percent' => __('درصد کش‌بک', 'golden-dashboard'),
                        'base_amount' => __('مبلغ پایه محاسبه کش‌بک', 'golden-dashboard'),
                        'gold_type_id' => __('نوع کیف پول طلا', 'golden-dashboard'),
                        'quantity' => __('مقدار', 'golden-dashboard'),
                        'price_per_unit' => __('قیمت هر واحد', 'golden-dashboard'),
                        'amount' => __('مبلغ', 'golden-dashboard'),
                        'tracking_code' => __('کد پیگیری', 'golden-dashboard'),
                    ];
                    $context_labels = [
                        'topup' => __('شارژ کیف پول', 'golden-dashboard'),
                        'gold'  => __('خرید طلا', 'golden-dashboard'),
                        'order' => __('خرید از فروشگاه', 'golden-dashboard'),
                    ];
                    foreach ($meta_data as $key => $value) : 
                        $label = isset($translated_keys[$key]) ? $translated_keys[$key] : esc_html($key);
                        $display_value = $value;
                        if (is_numeric($value) && in_array($key, ['fee_amount', 'net_amount', 'original_amount', 'amount', 'base_amount', 'price_per_unit'])) {
                            $display_value = gdb_price_plain($value);
                        } elseif (in_array($key, ['fee_percent', 'percent'])) {
                            $display_value = number_format($value, 2) . '%';
                        } elseif ($key === 'is_topup') {
                            $display_value = $value ? __('بله', 'golden-dashboard') : __('خیر', 'golden-dashboard');
                        } elseif ($key === 'context') {
                            $display_value = $context_labels[$value] ?? esc_html($value);
                        } elseif ($key === 'gold_type_id') {
                            $gold_type_row = class_exists('GDB_Gold_Wallet') ? GDB_Gold_Wallet::get_type((int) $value) : null;
                            $display_value = $gold_type_row ? $gold_type_row->name : $value;
                        } elseif ($key === 'quantity') {
                            $display_value = rtrim(rtrim(number_format((float) $value, 3), '0'), '.');
                        } elseif (is_array($value)) {
                            $display_value = '<pre>' . wp_json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                        }
                    ?>
                        <tr>
                            <th><?php echo esc_html($label); ?></th>
                            <td><?php echo is_array($value) ? '<pre>' . esc_html(wp_json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>' : esc_html($display_value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($audit_log)) : ?>
        <div class="gdb-detail-card gdb-no-print">
            <h2 class="gdb-detail-card-title"><?php esc_html_e('تاریخچه ویرایش', 'golden-dashboard'); ?></h2>
            <div class="gdb-detail-card-body">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('زمان', 'golden-dashboard'); ?></th>
                            <th><?php esc_html_e('کاربر', 'golden-dashboard'); ?></th>
                            <th><?php esc_html_e('اقدام', 'golden-dashboard'); ?></th>
                            <th><?php esc_html_e('توضیحات', 'golden-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($audit_log as $log) : 
                            $user = get_userdata($log->user_id);
                        ?>
                            <tr>
                                <td><?php echo esc_html(gdb_date_jalali($log->created_at, true)); ?></td>
                                <td><?php echo $user ? esc_html($user->display_name) : esc_html__('کاربر حذف شده', 'golden-dashboard'); ?></td>
                                <td>
                                    <?php if ($log->action === 'approved') : ?>
                                        <span style="color:#16a34a;"><?php esc_html_e('تایید', 'golden-dashboard'); ?></span>
                                    <?php elseif ($log->action === 'rejected') : ?>
                                        <span style="color:#dc2626;"><?php esc_html_e('رد', 'golden-dashboard'); ?></span>
                                    <?php elseif ($log->action === 'updated') : ?>
                                        <span style="color:#2563eb;"><?php esc_html_e('ویرایش', 'golden-dashboard'); ?></span>
                                    <?php else : ?>
                                        <?php echo esc_html($log->action); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($log->note); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($is_pending) : ?>
        <div class="gdb-detail-card gdb-no-print" style="border-color: #f59e0b;">
            <h2 class="gdb-detail-card-title" style="color:#d97706;"><?php esc_html_e('مدیریت درخواست', 'golden-dashboard'); ?></h2>
            <div class="gdb-detail-card-body">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('gdb_approve_withdraw', 'gdb_approve_nonce'); ?>
                    <input type="hidden" name="action" value="gdb_approve_withdraw">
                    <input type="hidden" name="request_id" value="<?php echo absint($transaction_id); ?>">
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="bank_transaction_id"><?php esc_html_e('شماره تراکنش بانکی', 'golden-dashboard'); ?></label></th>
                            <td><input type="text" name="bank_transaction_id" id="bank_transaction_id" class="widefat" required></td>
                        </tr>
                        <tr>
                            <th><label for="bank_date"><?php esc_html_e('تاریخ واریز بانکی', 'golden-dashboard'); ?></label></th>
                            <td><input type="text" class="widefat date-picker" autocomplete="off" name="bank_date" id="bank_date" value="<?php echo esc_attr(gdb_display_admin_date_input(gmdate('Y-m-d', current_time('timestamp')))); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="admin_note"><?php esc_html_e('یادداشت مدیر (اختیاری)', 'golden-dashboard'); ?></label></th>
                            <td><textarea name="admin_note" id="admin_note" class="widefat" rows="3"></textarea></td>
                        </tr>
                    </table>

                    <div style="display:flex; gap:10px; margin-top:10px;">
                        <button type="submit" class="button button-primary" style="background:#16a34a; border-color:#16a34a;"><?php esc_html_e('تایید و تکمیل درخواست', 'golden-dashboard'); ?></button>
                        <button type="button" class="button" id="gdb-show-reject-form" style="background:#dc2626; color:#fff; border-color:#dc2626;"><?php esc_html_e('رد درخواست', 'golden-dashboard'); ?></button>
                    </div>
                </form>

                <div id="gdb-reject-form" style="display:none; margin-top:20px; padding-top:20px; border-top:1px solid #ddd;">
                    <h3><?php esc_html_e('رد درخواست', 'golden-dashboard'); ?></h3>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('gdb_reject_withdraw', 'gdb_reject_nonce'); ?>
                        <input type="hidden" name="action" value="gdb_reject_withdraw">
                        <input type="hidden" name="request_id" value="<?php echo absint($transaction_id); ?>">
                        <table class="form-table">
                            <tr>
                                <th><label for="reason"><?php esc_html_e('دلیل رد', 'golden-dashboard'); ?></label></th>
                                <td><textarea name="reason" id="reason" class="widefat" rows="3" required></textarea></td>
                            </tr>
                        </table>
                        <button type="submit" class="button" style="background:#dc2626; color:#fff; border-color:#dc2626;"><?php esc_html_e('تایید رد', 'golden-dashboard'); ?></button>
                        <button type="button" class="button" id="gdb-hide-reject-form"><?php esc_html_e('انصراف', 'golden-dashboard'); ?></button>
                    </form>
                </div>
            </div>
        </div>

        <script>
        (function($) {
            $('#gdb-show-reject-form').on('click', function() {
                $('#gdb-reject-form').slideDown();
                $(this).hide();
            });
            $('#gdb-hide-reject-form').on('click', function() {
                $('#gdb-reject-form').slideUp();
                $('#gdb-show-reject-form').show();
            });
        })(jQuery);
        </script>
    <?php endif; ?>
</div>

<style>
    .gdb-detail-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .gdb-detail-card-title {
        margin: 0;
        padding: 15px 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
    }
    .gdb-detail-card-body {
        padding: 20px;
    }
    .gdb-detail-table {
        width: 100%;
        border-collapse: collapse;
    }
    .gdb-detail-table th {
        width: 25%;
        text-align: right;
        padding: 10px 12px;
        font-weight: 600;
        color: #374151;
        vertical-align: top;
        border-bottom: 1px solid #f1f5f9;
    }
    .gdb-detail-table td {
        padding: 10px 12px;
        vertical-align: top;
        border-bottom: 1px solid #f1f5f9;
        color: #111827;
    }
    .gdb-detail-table tr:last-child th,
    .gdb-detail-table tr:last-child td {
        border-bottom: none;
    }

    .gdb-status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .gdb-status-pending { background: #fef3c7; color: #d97706; }
    .gdb-status-processing { background: #dbeafe; color: #2563eb; }
    .gdb-status-completed { background: #dcfce7; color: #16a34a; }
    .gdb-status-on-hold { background: #f3e8ff; color: #9333ea; }
    .gdb-status-cancelled { background: #e5e7eb; color: #6b7280; }
    .gdb-status-refunded { background: #fef2f2; color: #dc2626; }
    .gdb-status-failed { background: #fee2e2; color: #dc2626; }
    .gdb-status-rejected { background: #fee2e2; color: #dc2626; }

    @media print {
        #adminmenuwrap, #adminmenuback, #wpadminbar, #wpfooter,
        .notice, .button, form, .tablenav, .description,
        .gdb-no-print, .wrap > h1 + .button,
        .wrap > .button, .wrap > .button-primary,
        a[href*="edit-user"], #gdb-reject-form,
        #gdb-show-reject-form, #gdb-hide-reject-form {
            display: none !important;
        }

        body {
            background: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
            font-family: 'Courier New', Courier, monospace !important;
            font-size: 12px !important;
            color: #000 !important;
        }
        #wpcontent {
            margin: 0 !important;
            padding: 20px !important;
        }
        .wrap {
            max-width: 100% !important;
            margin: 0 !important;
        }
        .wrap h1 {
            font-size: 18px !important;
            text-align: center !important;
            margin-bottom: 20px !important;
            border-bottom: 2px solid #000 !important;
            padding-bottom: 10px !important;
        }

        .gdb-print-card {
            border: 1px solid #000 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            margin-bottom: 15px !important;
            page-break-inside: avoid;
            background: #fff !important;
        }
        .gdb-print-card .gdb-detail-card-title {
            background: #f5f5f5 !important;
            border-bottom: 1px solid #000 !important;
            font-size: 14px !important;
            font-weight: bold !important;
            padding: 10px 15px !important;
        }
        .gdb-print-card .gdb-detail-card-body {
            padding: 10px 15px !important;
        }
        .gdb-detail-table th {
            background: #fafafa !important;
            font-weight: bold !important;
            border-bottom: 1px solid #ccc !important;
            padding: 6px 8px !important;
            width: 30% !important;
        }
        .gdb-detail-table td {
            border-bottom: 1px solid #eee !important;
            padding: 6px 8px !important;
        }
        .gdb-detail-table tr:last-child th,
        .gdb-detail-table tr:last-child td {
            border-bottom: none !important;
        }

        .gdb-status-badge {
            border: 1px solid #000 !important;
            background: transparent !important;
            color: #000 !important;
        }

        a[href*="edit-user"] {
            display: none !important;
        }

        .gdb-print-card .gdb-detail-card-title::before {
            content: "▍ ";
        }
        .wrap h1::after {
            content: "رسید تراکنش";
            display: block;
            font-size: 14px;
            font-weight: normal;
            color: #555;
            margin-top: 5px;
        }
        .gdb-print-card .gdb-detail-table td code {
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
        }
    }
</style>
<?php // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange ?>

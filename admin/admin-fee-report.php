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

global $wpdb;
$table_trans = $wpdb->prefix . 'gd_wallet_transactions';
$table_wallet = $wpdb->prefix . 'gd_user_wallet';

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin report filtering; no data is written or changed here.
$date_from = isset($_GET['date_from']) ? gdb_normalize_admin_date_input(sanitize_text_field(wp_unslash($_GET['date_from']))) : '';
$date_to = isset($_GET['date_to']) ? gdb_normalize_admin_date_input(sanitize_text_field(wp_unslash($_GET['date_to']))) : '';
$filter_user_id = isset($_GET['user_id']) ? absint(wp_unslash($_GET['user_id'])) : 0;
// phpcs:enable WordPress.Security.NonceVerification.Recommended
$filter_user = $filter_user_id ? get_userdata($filter_user_id) : false;

$where = "WHERE fee_amount > 0";
$params = [];
if ($date_from) {
    $where .= " AND created_at >= %s";
    $params[] = $date_from . ' 00:00:00';
}
if ($date_to) {
    $where .= " AND created_at <= %s";
    $params[] = $date_to . ' 23:59:59';
}
if ($filter_user_id) {
    $where .= " AND user_id = %d";
    $params[] = $filter_user_id;
}

$summary_sql = "SELECT COUNT(*) as cnt, COALESCE(SUM(fee_amount),0) as total_fee, COALESCE(AVG(fee_amount),0) as avg_fee, COALESCE(SUM(amount),0) as total_gross FROM {$table_trans} {$where}";
$summary = $params ? $wpdb->get_row($wpdb->prepare($summary_sql, $params)) : $wpdb->get_row($summary_sql);

$total_liability = (float) $wpdb->get_var("SELECT COALESCE(SUM(balance),0) FROM {$table_wallet}");

$per_user = [];
if (!$filter_user_id) {
    $per_user_sql = "SELECT user_id, COUNT(*) as cnt, SUM(fee_amount) as total_fee, SUM(amount) as total_gross
                      FROM {$table_trans} {$where}
                      GROUP BY user_id ORDER BY total_fee DESC LIMIT 100";
    $per_user = $params ? $wpdb->get_results($wpdb->prepare($per_user_sql, $params)) : $wpdb->get_results($per_user_sql);
}

$user_detail = null;
$user_fee_transactions = [];
if ($filter_user_id) {
    $detail_summary_where = "user_id = %d AND fee_amount > 0";
    $detail_summary_params = [$filter_user_id];
    if ($date_from) {
        $detail_summary_where .= " AND created_at >= %s";
        $detail_summary_params[] = $date_from . ' 00:00:00';
    }
    if ($date_to) {
        $detail_summary_where .= " AND created_at <= %s";
        $detail_summary_params[] = $date_to . ' 23:59:59';
    }
    // phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $detail_summary_where/$detail_summary_params are built dynamically from a fixed set of %d/%s placeholders always pushed together in the same order and count; manually verified to match at every branch.
    $user_detail = $wpdb->get_row($wpdb->prepare(
        "SELECT COUNT(*) as cnt, COALESCE(SUM(fee_amount),0) as total_fee, COALESCE(SUM(net_amount),0) as total_net, COALESCE(SUM(amount),0) as total_gross
         FROM {$table_trans} WHERE {$detail_summary_where}",
        $detail_summary_params
    ));
    // phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare




    $detail_where = "WHERE user_id = %d AND fee_amount > 0";
    $detail_params = [$filter_user_id];
    if ($date_from) {
        $detail_where .= " AND created_at >= %s";
        $detail_params[] = $date_from . ' 00:00:00';
    }
    if ($date_to) {
        $detail_where .= " AND created_at <= %s";
        $detail_params[] = $date_to . ' 23:59:59';
    }
    // phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $detail_where/$detail_params are built dynamically from a fixed set of %d/%s placeholders always pushed together in the same order and count; manually verified to match at every branch.
    $user_fee_transactions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_trans} {$detail_where} ORDER BY created_at DESC LIMIT 200",
        $detail_params
    ));
    // phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
}
?>
<div class="wrap">
    <h1><?php esc_html_e('گزارش کارمزد و درآمد', 'golden-dashboard'); ?></h1>
    <p class="description">
        <?php esc_html_e('این گزارش، مبلغی که واقعاً از محل کارمزدِ برداشت‌ها به‌عنوان درآمد جمع کرده‌اید را نشان می‌دهد - جدا از موجودیِ کیف‌پول کاربران که امانتِ آن‌هاست، نه درآمدِ شما.', 'golden-dashboard'); ?>
    </p>

    <form method="get" action="" style="margin: 20px 0; background:#f8fafc; padding:15px; border-radius:8px; display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
        <input type="hidden" name="page" value="gdb-fee-report">
        <div>
            <label style="display:block; font-size:12px; color:#666; margin-bottom:4px;"><?php esc_html_e('از تاریخ', 'golden-dashboard'); ?></label>
            <input type="text" class="date-picker" autocomplete="off" name="date_from" value="<?php echo esc_attr(gdb_display_admin_date_input($date_from)); ?>" placeholder="<?php esc_attr_e('از تاریخ', 'golden-dashboard'); ?>">
        </div>
        <div>
            <label style="display:block; font-size:12px; color:#666; margin-bottom:4px;"><?php esc_html_e('تا تاریخ', 'golden-dashboard'); ?></label>
            <input type="text" class="date-picker" autocomplete="off" name="date_to" value="<?php echo esc_attr(gdb_display_admin_date_input($date_to)); ?>" placeholder="<?php esc_attr_e('تا تاریخ', 'golden-dashboard'); ?>">
        </div>
        <div style="min-width:250px;">
            <label style="display:block; font-size:12px; color:#666; margin-bottom:4px;"><?php esc_html_e('کاربر خاص (اختیاری)', 'golden-dashboard'); ?></label>
            <select id="gdb-fee-report-user-search" name="user_id" style="width:100%;">
                <?php if ($filter_user) : ?>
                    <option value="<?php echo esc_attr($filter_user->ID); ?>" selected>
                        <?php echo esc_html($filter_user->display_name . ' (' . $filter_user->user_email . ')'); ?>
                    </option>
                <?php endif; ?>
            </select>
        </div>
        <div>
            <button type="submit" class="button button-primary"><?php esc_html_e('اعمال فیلتر', 'golden-dashboard'); ?></button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-fee-report')); ?>" class="button"><?php esc_html_e('پاک‌کردن فیلتر', 'golden-dashboard'); ?></a>
        </div>
    </form>

    <div class="gdb-dashboard-grid">
        <div class="gdb-dashboard-card">
            <h3><?php esc_html_e('کارمزد جمع‌شده (درآمدِ واقعی شما)', 'golden-dashboard'); ?></h3>
            <span class="gdb-stat-number gdb-color-green"><?php echo esc_html(gdb_price_localized($summary->total_fee)); ?></span>
            <?php if ($date_from || $date_to) : ?>
                <div class="gdb-stat-desc"><?php esc_html_e('در بازه‌ی انتخاب‌شده', 'golden-dashboard'); ?></div>
            <?php else : ?>
                <div class="gdb-stat-desc"><?php esc_html_e('از ابتدا تاکنون', 'golden-dashboard'); ?></div>
            <?php endif; ?>
        </div>
        <div class="gdb-dashboard-card">
            <h3><?php esc_html_e('تعداد تراکنشِ کارمزددار', 'golden-dashboard'); ?></h3>
            <span class="gdb-stat-number gdb-color-blue"><?php echo esc_html(number_format_i18n($summary->cnt)); ?></span>
        </div>
        <div class="gdb-dashboard-card">
            <h3><?php esc_html_e('میانگین کارمزد هر تراکنش', 'golden-dashboard'); ?></h3>
            <span class="gdb-stat-number gdb-color-purple"><?php echo esc_html(gdb_price_localized($summary->avg_fee)); ?></span>
        </div>
        <div class="gdb-dashboard-card">
            <h3><?php esc_html_e('بدهیِ شما به کاربران (موجودیِ کل کیف‌پول‌ها)', 'golden-dashboard'); ?></h3>
            <span class="gdb-stat-number gdb-color-warning"><?php echo esc_html(gdb_price_localized($total_liability)); ?></span>
            <div class="gdb-stat-desc"><?php esc_html_e('این پول امانتِ کاربران است، نه درآمدِ شما', 'golden-dashboard'); ?></div>
        </div>
    </div>

    <?php if ($filter_user && $user_detail) : ?>
        <div class="gdb-detail-card" style="margin-top:25px;">
            <div class="gdb-detail-card-title">
                <?php
                /* translators: %s: user display name */
                echo esc_html(sprintf(__('خلاصه‌ی تراکنش‌های کارمزددارِ %s', 'golden-dashboard'), $filter_user->display_name));
                ?>
            </div>
            <div class="gdb-detail-card-body">
                <div class="gdb-dashboard-grid">
                    <div class="gdb-dashboard-card">
                        <h3><?php esc_html_e('مجموع برداشت (ناخالص)', 'golden-dashboard'); ?></h3>
                        <span class="gdb-stat-number gdb-color-red"><?php echo esc_html(gdb_price_localized($user_detail->total_gross)); ?></span>
                    </div>
                    <div class="gdb-dashboard-card">
                        <h3><?php esc_html_e('مجموع کارمزد پرداختی', 'golden-dashboard'); ?></h3>
                        <span class="gdb-stat-number gdb-color-warning"><?php echo esc_html(gdb_price_localized($user_detail->total_fee)); ?></span>
                    </div>
                    <div class="gdb-dashboard-card">
                        <h3><?php esc_html_e('مجموع خالص دریافتی', 'golden-dashboard'); ?></h3>
                        <span class="gdb-stat-number gdb-color-green"><?php echo esc_html(gdb_price_localized($user_detail->total_net)); ?></span>
                    </div>
                </div>
                <p style="margin-top:15px;">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-manual-credit&user_id=' . $filter_user->ID)); ?>" class="button"><?php esc_html_e('مشاهده‌ی کامل تراکنش‌های این کاربر', 'golden-dashboard'); ?></a>
                </p>

                <?php if ($user_fee_transactions) :
                    $fee_status_labels = [
                        'pending'    => __('در انتظار', 'golden-dashboard'),
                        'processing' => __('در حال پردازش', 'golden-dashboard'),
                        'completed'  => __('تکمیل شده', 'golden-dashboard'),
                        'cancelled'  => __('لغو شده', 'golden-dashboard'),
                        'refunded'   => __('بازگشت وجه', 'golden-dashboard'),
                        'failed'     => __('ناموفق', 'golden-dashboard'),
                        'rejected'   => __('رد شده', 'golden-dashboard'),
                    ];
                    ?>
                    <h3 style="margin-top:20px;"><?php esc_html_e('تراکنش‌های کارمزددار در همین بازه', 'golden-dashboard'); ?></h3>
                    <div style="overflow-x:auto;">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('تاریخ', 'golden-dashboard'); ?></th>
                                    <th><?php esc_html_e('نوع', 'golden-dashboard'); ?></th>
                                    <th><?php esc_html_e('مبلغ ناخالص', 'golden-dashboard'); ?></th>
                                    <th><?php esc_html_e('کارمزد', 'golden-dashboard'); ?></th>
                                    <th><?php esc_html_e('خالص', 'golden-dashboard'); ?></th>
                                    <th><?php esc_html_e('وضعیت', 'golden-dashboard'); ?></th>
                                    <th style="width:100px;"><?php esc_html_e('عملیات', 'golden-dashboard'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_fee_transactions as $ftx) : ?>
                                    <tr>
                                        <td><?php echo esc_html(gdb_date_jalali($ftx->created_at, true)); ?></td>
                                        <td><?php echo esc_html(gdb_transaction_label($ftx, $ftx->type === 'credit')); ?></td>
                                        <td><?php echo wp_kses_post(gdb_price($ftx->amount)); ?></td>
                                        <td><?php echo wp_kses_post(gdb_price($ftx->fee_amount)); ?></td>
                                        <td><?php echo wp_kses_post(gdb_price($ftx->net_amount)); ?></td>
                                        <td><span class="gdb-status-badge gdb-status-<?php echo esc_attr($ftx->status); ?>"><?php echo esc_html(isset($fee_status_labels[$ftx->status]) ? $fee_status_labels[$ftx->status] : $ftx->status); ?></span></td>
                                        <td><a href="<?php echo esc_url(admin_url('admin.php?page=gdb-transaction-detail&id=' . $ftx->id)); ?>" class="button button-small"><?php esc_html_e('جزئیات', 'golden-dashboard'); ?></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif (!$filter_user_id) : ?>
        <h2 style="margin-top:30px;"><?php esc_html_e('تفکیک بر اساس کاربر (۱۰۰ کاربر برتر از نظر کارمزد)', 'golden-dashboard'); ?></h2>
        <?php if ($per_user) : ?>
            <div style="overflow-x:auto;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('کاربر', 'golden-dashboard'); ?></th>
                            <th><?php esc_html_e('تعداد تراکنش', 'golden-dashboard'); ?></th>
                            <th><?php esc_html_e('مجموع ناخالص', 'golden-dashboard'); ?></th>
                            <th><?php esc_html_e('مجموع کارمزد', 'golden-dashboard'); ?></th>
                            <th style="width:120px;"><?php esc_html_e('عملیات', 'golden-dashboard'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($per_user as $row) :
                            $u = get_userdata($row->user_id);
                        ?>
                            <tr>
                                <td><?php
                                if ($u) {
                                    echo esc_html($u->display_name . ' (' . $u->user_email . ')');
                                } else {
                                    /* translators: %d: user ID */
                                    echo esc_html(sprintf(__('کاربر #%d (حذف‌شده)', 'golden-dashboard'), $row->user_id));
                                }
                                ?></td>
                                <td><?php echo esc_html(number_format_i18n($row->cnt)); ?></td>
                                <td><?php echo wp_kses_post(gdb_price($row->total_gross)); ?></td>
                                <td><strong><?php echo wp_kses_post(gdb_price($row->total_fee)); ?></strong></td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg('user_id', $row->user_id)); ?>" class="button button-small"><?php esc_html_e('مشاهده', 'golden-dashboard'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <p><?php esc_html_e('در این بازه، هیچ تراکنشِ کارمزددار‌ی ثبت نشده است.', 'golden-dashboard'); ?></p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#gdb-fee-report-user-search').select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    action: 'gdb_search_users',
                    nonce: '<?php echo esc_js(wp_create_nonce('gdb_search_users_nonce')); ?>',
                    term: params.term,
                    page: params.page || 1,
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return { results: data.data, pagination: { more: data.more } };
            },
            cache: true
        },
        minimumInputLength: 2,
        placeholder: '<?php echo esc_js(__('نام کاربر را وارد کنید...', 'golden-dashboard')); ?>',
        allowClear: true,
        language: 'fa',
        dir: 'rtl',
        templateResult: function(user) {
            if (user.loading) return user.text;
            return user.display_name ? (user.display_name + ' (' + user.user_email + ')') : user.text;
        },
        templateSelection: function(user) {
            return user.display_name || user.text;
        }
    });
});
</script>
<?php // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange ?>

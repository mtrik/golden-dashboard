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
$table = $wpdb->prefix . 'gd_wallet_transactions';
$table_wallet = $wpdb->prefix . 'gd_user_wallet';

$pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE transaction_type = 'withdraw_request' AND status = 'pending'");
$total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
$total_credit = $wpdb->get_var("SELECT SUM(amount) FROM {$table} WHERE type = 'credit' AND status = 'completed'");
$total_debit = $wpdb->get_var("SELECT SUM(amount) FROM {$table} WHERE type = 'debit' AND status = 'completed'");
$user_count = count_users();

$total_liability = (float) $wpdb->get_var("SELECT COALESCE(SUM(balance),0) FROM {$table_wallet}");
$total_fee_revenue = (float) $wpdb->get_var("SELECT COALESCE(SUM(fee_amount),0) FROM {$table}");

$pending_topup_orders = 0;
$pending_gold_orders = 0;
if (function_exists('wc_get_orders')) {
    // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- WooCommerce order meta flag lookup; no indexed alternative available via wc_get_orders().
    $pending_topup_orders = count(wc_get_orders([
        'limit' => -1,
        'status' => ['pending', 'processing', 'on-hold'],
        'meta_key' => '_gdb_is_topup',
        'meta_value' => 'yes',
        'return' => 'ids',
    ]));
    $pending_gold_orders = count(wc_get_orders([
        'limit' => -1,
        'status' => ['pending', 'processing', 'on-hold'],
        'meta_key' => '_gdb_is_gold_purchase',
        'meta_value' => 'yes',
        'return' => 'ids',
    ]));
    // phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
}

$gold_purchase_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE transaction_type = 'gold_purchase' AND status = 'completed'");
$gold_holdings = $wpdb->get_results(
    "SELECT gt.name, gt.unit_label, COALESCE(SUM(umw.balance),0) as total_balance
     FROM {$wpdb->prefix}gd_gold_wallet_types gt
     LEFT JOIN {$wpdb->prefix}gd_user_metal_wallet umw ON umw.gold_type_id = gt.id
     GROUP BY gt.id
     ORDER BY gt.id ASC"
);

$recent_transactions = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC LIMIT 10");

$widget_id = 'gdb-dashboard-' . uniqid();

?>
<div class="wrap">
    <h1><?php esc_html_e('گلدن داشبورد', 'golden-dashboard'); ?></h1>
    <p><?php esc_html_e('به پنل مدیریت گلدن داشبورد خوش آمدید.', 'golden-dashboard'); ?></p>

    <h2 class="gdb-section-title"><?php esc_html_e('نیازمند توجه', 'golden-dashboard'); ?></h2>
    <div class="gdb-dashboard-grid">
        <div class="gdb-dashboard-card gdb-clickable" onclick="window.location.href='<?php echo esc_url(admin_url('admin.php?page=gdb-withdraw-requests&status=pending')); ?>'">
            <h3><?php esc_html_e('درخواست‌های برداشت در انتظار', 'golden-dashboard'); ?></h3>
            <span class="gdb-stat-number gdb-color-warning"><?php echo esc_html(number_format_i18n($pending_count)); ?></span>
        </div>
        <div class="gdb-dashboard-card gdb-clickable" onclick="window.location.href='<?php echo esc_url(gdb_get_wc_orders_admin_url('_gdb_is_topup', 'yes', ['wc-pending', 'wc-processing', 'wc-on-hold'])); ?>'">
            <h3><?php esc_html_e('سفارشات شارژ کیف پول در انتظار', 'golden-dashboard'); ?></h3>
            <span class="gdb-stat-number gdb-color-blue"><?php echo esc_html(number_format_i18n($pending_topup_orders)); ?></span>
            <div class="gdb-stat-desc"><?php esc_html_e('سفارشات ووکامرس', 'golden-dashboard'); ?></div>
        </div>
        <div class="gdb-dashboard-card gdb-clickable" onclick="window.location.href='<?php echo esc_url(gdb_get_wc_orders_admin_url('_gdb_is_gold_purchase', 'yes', ['wc-pending', 'wc-processing', 'wc-on-hold'])); ?>'">
            <h3><?php esc_html_e('سفارشات خرید طلا در انتظار', 'golden-dashboard'); ?></h3>
            <span class="gdb-stat-number gdb-color-blue"><?php echo esc_html(number_format_i18n($pending_gold_orders)); ?></span>
            <div class="gdb-stat-desc"><?php esc_html_e('سفارشات ووکامرس', 'golden-dashboard'); ?></div>
        </div>
    </div>

    <h2 class="gdb-section-title"><?php esc_html_e('کیف پول نقدی', 'golden-dashboard'); ?></h2>
    <div class="gdb-dashboard-grid">
        <div class="gdb-dashboard-card">
            <h3><?php esc_html_e('بدهیِ شما به کاربران', 'golden-dashboard'); ?></h3>
            <span class="gdb-stat-number gdb-color-warning"><?php echo esc_html(gdb_price_localized($total_liability)); ?></span>
            <div class="gdb-stat-desc"><?php esc_html_e('مجموع موجودیِ کل کیف‌پول‌ها - امانتِ کاربران', 'golden-dashboard'); ?></div>
        </div>
        <div class="gdb-dashboard-card gdb-clickable" onclick="window.location.href='<?php echo esc_url(admin_url('admin.php?page=gdb-fee-report')); ?>'">
            <h3><?php esc_html_e('کارمزد جمع‌شده (درآمدِ واقعی شما)', 'golden-dashboard'); ?></h3>
            <span class="gdb-stat-number gdb-color-green"><?php echo esc_html(gdb_price_localized($total_fee_revenue)); ?></span>
            <div class="gdb-stat-desc"><?php esc_html_e('مشاهده‌ی گزارش کامل', 'golden-dashboard'); ?></div>
        </div>
        <div class="gdb-dashboard-card">
            <h3><?php esc_html_e('مجموع واریزها', 'golden-dashboard'); ?></h3>
            <span class="gdb-stat-number gdb-color-green"><?php echo esc_html(gdb_price_localized($total_credit)); ?></span>
        </div>
        <div class="gdb-dashboard-card">
            <h3><?php esc_html_e('مجموع برداشت‌ها (ناخالص)', 'golden-dashboard'); ?></h3>
            <span class="gdb-stat-number gdb-color-red"><?php echo esc_html(gdb_price_localized($total_debit)); ?></span>
        </div>
        <div class="gdb-dashboard-card gdb-clickable" onclick="window.location.href='<?php echo esc_url(admin_url('admin.php?page=gdb-transactions-history')); ?>'">
            <h3><?php esc_html_e('کل تراکنش‌ها', 'golden-dashboard'); ?></h3>
            <span class="gdb-stat-number gdb-color-blue"><?php echo esc_html(number_format_i18n($total_count)); ?></span>
        </div>
    </div>

    <h2 class="gdb-section-title"><?php esc_html_e('کیف پول طلا', 'golden-dashboard'); ?></h2>
    <div class="gdb-dashboard-grid">
        <div class="gdb-dashboard-card gdb-clickable" onclick="window.location.href='<?php echo esc_url(admin_url('admin.php?page=gdb-transactions-history&tx_type_filter=gold_purchase')); ?>'">
            <h3><?php esc_html_e('تعداد خرید طلای تکمیل‌شده', 'golden-dashboard'); ?></h3>
            <span class="gdb-stat-number gdb-color-blue"><?php echo esc_html(number_format_i18n($gold_purchase_count)); ?></span>
        </div>
        <?php foreach ($gold_holdings as $gh) : ?>
            <div class="gdb-dashboard-card">
                <h3><?php echo esc_html($gh->name); ?></h3>
                <span class="gdb-stat-number gdb-color-purple"><?php echo esc_html(gdb_format_quantity($gh->total_balance)); ?> <?php echo esc_html($gh->unit_label); ?></span>
                <div class="gdb-stat-desc"><?php esc_html_e('مجموع موجودیِ کاربران از این نوع', 'golden-dashboard'); ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($gold_holdings)) : ?>
            <div class="gdb-dashboard-card">
                <p class="description"><?php esc_html_e('هنوز هیچ نوع کیف‌پول طلایی تعریف نشده است.', 'golden-dashboard'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <h2 class="gdb-section-title"><?php esc_html_e('کاربران', 'golden-dashboard'); ?></h2>
    <div class="gdb-dashboard-grid">
        <div class="gdb-dashboard-card">
            <h3><?php esc_html_e('تعداد کاربران سایت', 'golden-dashboard'); ?></h3>
            <span class="gdb-stat-number gdb-color-purple"><?php echo esc_html(number_format_i18n($user_count['total_users'])); ?></span>
        </div>
    </div>

    <h2 class="gdb-section-title"><?php esc_html_e('روند فعالیت', 'golden-dashboard'); ?></h2>
    <div class="gdb-dashboard-chart-wrapper">
        <div class="gdb-chart-header">
            <h2><?php esc_html_e('تعداد تراکنش‌های روزانه', 'golden-dashboard'); ?></h2>
            <div class="gdb-chart-range-buttons">
                <button class="button gdb-chart-range" data-range="today"><?php esc_html_e('امروز', 'golden-dashboard'); ?></button>
                <button class="button gdb-chart-range active" data-range="7days"><?php esc_html_e('۷ روز', 'golden-dashboard'); ?></button>
                <button class="button gdb-chart-range" data-range="30days"><?php esc_html_e('۳۰ روز', 'golden-dashboard'); ?></button>
                <button class="button gdb-chart-range" data-range="90days"><?php esc_html_e('۹۰ روز', 'golden-dashboard'); ?></button>
                <button class="button gdb-chart-range" data-range="all"><?php esc_html_e('همه', 'golden-dashboard'); ?></button>
            </div>
        </div>
        <div id="gdb-chart-container" style="background:#fff; padding:20px; border-radius:8px; border:1px solid #ddd; margin-top:10px;">
            <div id="gdb-chart-loading" style="text-align:center; padding:40px 0; display:none;">
                <span class="spinner is-active" style="float:none;"></span> <?php esc_html_e('در حال بارگذاری...', 'golden-dashboard'); ?>
            </div>
            <div id="gdb-chart-bars" style="display:flex; align-items:flex-end; gap:10px; height:150px; padding-top:10px; border-bottom:2px solid #ddd; overflow-x:auto; min-height:150px;">
            </div>
        </div>
    </div>

    <h2 class="gdb-section-title"><?php esc_html_e('آخرین تراکنش‌ها', 'golden-dashboard'); ?></h2>
    <?php if ($recent_transactions) :
        $gdb_dash_status_labels = [
            'pending'    => __('در انتظار', 'golden-dashboard'),
            'processing' => __('در حال پردازش', 'golden-dashboard'),
            'completed'  => __('تکمیل شده', 'golden-dashboard'),
            'on-hold'    => __('در انتظار', 'golden-dashboard'),
            'cancelled'  => __('لغو شده', 'golden-dashboard'),
            'refunded'   => __('بازگشت وجه', 'golden-dashboard'),
            'failed'     => __('ناموفق', 'golden-dashboard'),
            'rejected'   => __('رد شده', 'golden-dashboard'),
        ];
        ?>
        <div style="overflow-x:auto;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('تاریخ', 'golden-dashboard'); ?></th>
                        <th><?php esc_html_e('کاربر', 'golden-dashboard'); ?></th>
                        <th><?php esc_html_e('نوع', 'golden-dashboard'); ?></th>
                        <th><?php esc_html_e('مبلغ', 'golden-dashboard'); ?></th>
                        <th><?php esc_html_e('وضعیت', 'golden-dashboard'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_transactions as $rtx) :
                        $ru = get_userdata($rtx->user_id);
                        
                        
                        
                        
                        if ($rtx->transaction_type === 'withdraw_request') {
                            $row_link = admin_url('admin.php?page=gdb-withdraw-requests&user_id=' . $rtx->user_id);
                        } else {
                            $row_link = admin_url('admin.php?page=gdb-transaction-detail&id=' . $rtx->id);
                        }
                    ?>
                        <tr class="gdb-clickable-row" onclick="window.location.href='<?php echo esc_url($row_link); ?>'" style="cursor:pointer;">
                            <td><?php echo esc_html(gdb_date_jalali($rtx->created_at, true)); ?></td>
                            <td><?php echo $ru ? esc_html($ru->display_name) : '#' . absint($rtx->user_id); ?></td>
                            <td><?php echo esc_html(gdb_transaction_label($rtx, $rtx->type === 'credit')); ?></td>
                            <td style="color:<?php echo $rtx->type === 'credit' ? '#16a34a' : '#dc2626'; ?>;"><?php echo wp_kses_post(gdb_price($rtx->amount)); ?></td>
                            <td><span class="gdb-status-badge gdb-status-<?php echo esc_attr($rtx->status); ?>"><?php echo esc_html(isset($gdb_dash_status_labels[$rtx->status]) ? $gdb_dash_status_labels[$rtx->status] : $rtx->status); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('هنوز هیچ تراکنشی ثبت نشده است.', 'golden-dashboard'); ?></p>
    <?php endif; ?>

    <div style="margin-top:20px; display:flex; gap:10px; flex-wrap:wrap;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-withdraw-requests')); ?>" class="button button-primary"><?php esc_html_e('مدیریت برداشت‌ها', 'golden-dashboard'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-transactions-history')); ?>" class="button"><?php esc_html_e('مشاهده تاریخچه تراکنش‌ها', 'golden-dashboard'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-fee-report')); ?>" class="button"><?php esc_html_e('گزارش کارمزد و درآمد', 'golden-dashboard'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-settings')); ?>" class="button"><?php esc_html_e('تنظیمات', 'golden-dashboard'); ?></a>
    </div>
</div>

<style>
    .gdb-dashboard-chart-wrapper {
        margin-top: 30px;
    }
    .gdb-chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .gdb-chart-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
    }
    .gdb-chart-range-buttons {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .gdb-chart-range-buttons .button {
        font-size: 12px;
        padding: 4px 12px;
        border-radius: 4px;
    }
    .gdb-chart-range-buttons .button.active {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
    }
    .gdb-chart-bar {
        flex: 1;
        text-align: center;
        min-width: 30px;
    }
    .gdb-chart-bar .bar {
        background: #2563eb;
        border-radius: 4px 4px 0 0;
        min-height: 4px;
        width: 100%;
        transition: height 0.3s ease;
    }
    .gdb-chart-bar .date-label {
        font-size: 10px;
        margin-top: 4px;
        color: #6b7280;
        white-space: nowrap;
    }
    .gdb-chart-bar .count-label {
        font-size: 10px;
        font-weight: 700;
        color: #1f2937;
    }

    @media (max-width: 768px) {
        .gdb-chart-header {
            flex-direction: column;
            align-items: stretch;
        }
        .gdb-chart-range-buttons {
            justify-content: center;
        }
        #gdb-chart-bars {
            gap: 4px;
            height: 120px;
        }
        .gdb-chart-bar .date-label {
            font-size: 8px;
        }
    }
    @media (max-width: 480px) {
        #gdb-chart-bars {
            height: 100px;
        }
        .gdb-chart-range-buttons .button {
            font-size: 10px;
            padding: 2px 8px;
        }
    }
</style>

<script>
jQuery(document).ready(function($) {
    if (typeof gdbAdminVars === 'undefined') {
        console.warn('gdbAdminVars not defined. Creating fallback.');
        window.gdbAdminVars = {
            ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo esc_js(wp_create_nonce('gdb_filter_nonce')); ?>'
        };
    }

    function gdb_gregorian_to_jalali(gy, gm, gd) {
        var g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        var j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        
        var gy2 = gy - 1600;
        var gm2 = gm - 1;
        var gd2 = gd - 1;
        
        var g_day_no = 365 * gy2 + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400);
        for (var i = 0; i < gm2; i++) {
            g_day_no += g_days_in_month[i];
        }
        if (gm2 > 1 && ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0))) {
            g_day_no++;
        }
        g_day_no += gd2;
        
        var j_day_no = g_day_no - 79;
        var j_np = Math.floor(j_day_no / 12053);
        j_day_no %= 12053;
        var jy = 979 + 33 * j_np + 4 * Math.floor(j_day_no / 1461);
        j_day_no %= 1461;
        if (j_day_no >= 366) {
            jy += Math.floor((j_day_no - 1) / 365);
            j_day_no = (j_day_no - 1) % 365;
        }
        var jm = 0;
        for (var j = 0; j < 11 && j_day_no >= j_days_in_month[j]; j++) {
            j_day_no -= j_days_in_month[j];
        }
        jm = j + 1;
        var jd = j_day_no + 1;
        return [jy, jm, jd];
    }

    var currentRange = '7days';
    var chartContainer = $('#gdb-chart-bars');
    var loading = $('#gdb-chart-loading');

    function loadChart(range) {
        loading.show();
        chartContainer.empty();
        $.ajax({
            url: gdbAdminVars.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'gdb_get_chart_data',
                nonce: gdbAdminVars.nonce,
                range: range
            },
            success: function(response) {
                if (response.success && response.data.data) {
                    var data = response.data.data;
                    var maxCount = 1;
                    if (data.length > 0) {
                        maxCount = Math.max.apply(null, data.map(function(item) { return item.count; }));
                    }
                    var html = '';
                    data.reverse();
                    data.forEach(function(item) {
                        var height = (item.count / maxCount) * 130;
                        var dateLabel = item.date ? item.date : '';
                        if (dateLabel) {
                            var parts = dateLabel.split('-');
                            var jalali = gdb_gregorian_to_jalali(parseInt(parts[0]), parseInt(parts[1]), parseInt(parts[2]));
                            if (jalali) {
                                dateLabel = jalali[0] + '/' + ('0' + jalali[1]).slice(-2) + '/' + ('0' + jalali[2]).slice(-2);
                            }
                        }
                        html += '<div class="gdb-chart-bar">' +
                            '<div class="bar" style="height:' + height + 'px;"></div>' +
                            '<div class="count-label">' + item.count + '</div>' +
                            '<div class="date-label">' + dateLabel + '</div>' +
                            '</div>';
                    });
                    chartContainer.html(html);
                } else {
                    chartContainer.html('<p style="text-align:center;width:100%;color:#6b7280;">' + (response.data.message || 'خطا در بارگذاری داده‌ها') + '</p>');
                }
            },
            error: function() {
                chartContainer.html('<p style="text-align:center;width:100%;color:#dc2626;">خطا در ارتباط با سرور</p>');
            },
            complete: function() {
                loading.hide();
            }
        });
    }

    loadChart(currentRange);

    $('.gdb-chart-range').on('click', function() {
        var range = $(this).data('range');
        if (range === currentRange) return;
        currentRange = range;
        $('.gdb-chart-range').removeClass('active');
        $(this).addClass('active');
        loadChart(range);
    });
});
</script>
<?php // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange ?>

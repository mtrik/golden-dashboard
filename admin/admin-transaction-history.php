<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'golden-dashboard'));
}

global $wpdb;
$table = $wpdb->prefix . 'gd_wallet_transactions';


$type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? gdb_normalize_admin_date_input(sanitize_text_field($_GET['date_from'])) : '';
$date_to = isset($_GET['date_to']) ? gdb_normalize_admin_date_input(sanitize_text_field($_GET['date_to'])) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

$per_page = 50;
$page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($page - 1) * $per_page;

$where = ['1=1'];
$params = [];



if ($type && in_array($type, ['credit', 'debit'])) {
    $where[] = 'type = %s';
    $params[] = $type;
}
if ($status) {
    $where[] = 'status = %s';
    $params[] = $status;
}
if ($date_from) {
    $where[] = 'DATE(created_at) >= %s';
    $params[] = $date_from;
}
if ($date_to) {
    $where[] = 'DATE(created_at) <= %s';
    $params[] = $date_to;
}
if ($search) {
    $where[] = '(description LIKE %s OR tracking_code LIKE %s OR transaction_type LIKE %s OR user_id IN (SELECT ID FROM ' . $wpdb->users . ' WHERE display_name LIKE %s OR user_login LIKE %s OR user_email LIKE %s))';
    $search_like = '%' . $wpdb->esc_like($search) . '%';
    $params = array_merge($params, [$search_like, $search_like, $search_like, $search_like, $search_like, $search_like]);
}

$where_sql = implode(' AND ', $where);
$count_sql = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", $params);
$total = (int) $wpdb->get_var($count_sql);

$sql = $wpdb->prepare(
    "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d",
    array_merge($params, [$per_page, $offset])
);
$transactions = $wpdb->get_results($sql);
$pages = ceil($total / $per_page);





$status_labels = [
    'pending'    => __('در انتظار', 'golden-dashboard'),
    'processing' => __('در حال پردازش', 'golden-dashboard'),
    'completed'  => __('تکمیل شده', 'golden-dashboard'),
    'on-hold'    => __('در انتظار', 'golden-dashboard'),
    'cancelled'  => __('لغو شده', 'golden-dashboard'),
    'refunded'   => __('بازگشت وجه', 'golden-dashboard'),
    'failed'     => __('ناموفق', 'golden-dashboard'),
    'rejected'   => __('رد شده', 'golden-dashboard'),
];

$status_classes = [
    'pending'    => 'gdb-status-pending',
    'processing' => 'gdb-status-processing',
    'completed'  => 'gdb-status-completed',
    'on-hold'    => 'gdb-status-on-hold',
    'cancelled'  => 'gdb-status-cancelled',
    'refunded'   => 'gdb-status-refunded',
    'failed'     => 'gdb-status-failed',
    'rejected'   => 'gdb-status-rejected',
];

?>
<div class="wrap">
    <h1><?php _e('تاریخچه کامل تراکنش‌ها', 'golden-dashboard'); ?></h1>
    
    <div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
        <a href="<?php echo admin_url('admin-post.php?action=gdb_export_transactions_csv&' . http_build_query($_GET)); ?>" class="button button-primary">
            <span class="dashicons dashicons-download" style="margin-top:4px;"></span> <?php _e('خروجی CSV', 'golden-dashboard'); ?>
        </a>
        <button type="button" class="button" onclick="window.print();">
            <span class="dashicons dashicons-printer" style="margin-top:4px;"></span> <?php _e('چاپ', 'golden-dashboard'); ?>
        </button>
    </div>

    <form method="get" action="" style="margin-bottom:20px; background:#f8fafc; padding:15px; border-radius:8px; display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
        <input type="hidden" name="page" value="gdb-transactions-history">


        <select name="type">
            <option value=""><?php _e('همه انواع', 'golden-dashboard'); ?></option>
            <option value="credit" <?php selected($type, 'credit'); ?>><?php _e('واریز', 'golden-dashboard'); ?></option>
            <option value="debit" <?php selected($type, 'debit'); ?>><?php _e('برداشت', 'golden-dashboard'); ?></option>
        </select>

        <select name="status">
            <option value=""><?php _e('همه وضعیت‌ها', 'golden-dashboard'); ?></option>
            <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('در انتظار', 'golden-dashboard'); ?></option>
            <option value="completed" <?php selected($status, 'completed'); ?>><?php _e('تکمیل شده', 'golden-dashboard'); ?></option>
            <option value="on-hold" <?php selected($status, 'on-hold'); ?>><?php _e('در انتظار', 'golden-dashboard'); ?></option>
            <option value="rejected" <?php selected($status, 'rejected'); ?>><?php _e('رد شده', 'golden-dashboard'); ?></option>
            <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php _e('لغو شده', 'golden-dashboard'); ?></option>
            <option value="refunded" <?php selected($status, 'refunded'); ?>><?php _e('بازگشت وجه', 'golden-dashboard'); ?></option>
            <option value="failed" <?php selected($status, 'failed'); ?>><?php _e('ناموفق', 'golden-dashboard'); ?></option>
        </select>

        <input type="text" class="date-picker" autocomplete="off" name="date_from" value="<?php echo esc_attr(gdb_display_admin_date_input($date_from)); ?>" placeholder="<?php _e('از تاریخ', 'golden-dashboard'); ?>">
        <input type="text" class="date-picker" autocomplete="off" name="date_to" value="<?php echo esc_attr(gdb_display_admin_date_input($date_to)); ?>" placeholder="<?php _e('تا تاریخ', 'golden-dashboard'); ?>">

        <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('جستجوی کاربر...', 'golden-dashboard'); ?>" style="min-width:150px;">

        <button type="submit" class="button"><?php _e('فیلتر', 'golden-dashboard'); ?></button>
        <a href="<?php echo admin_url('admin.php?page=gdb-transactions-history'); ?>" class="button"><?php _e('بازنشانی', 'golden-dashboard'); ?></a>
    </form>

    <?php if ($transactions) : ?>
        <div style="overflow-x:auto;">
            <table class="wp-list-table widefat fixed striped" id="gdb-transactions-table">
                <thead>
                    <tr>
                        <th style="width:60px;"><?php _e('شناسه', 'golden-dashboard'); ?></th>
                        <th><?php _e('کاربر', 'golden-dashboard'); ?></th>
                        <th><?php _e('نوع', 'golden-dashboard'); ?></th>
                        <th><?php _e('مبلغ', 'golden-dashboard'); ?></th>
                        <th><?php _e('کارمزد', 'golden-dashboard'); ?></th>
                        <th><?php _e('مبلغ خالص', 'golden-dashboard'); ?></th>
                        <th><?php _e('وضعیت', 'golden-dashboard'); ?></th>
                        <th style="min-width:120px; white-space:nowrap;"><?php _e('کد پیگیری', 'golden-dashboard'); ?></th>
                        <th><?php _e('تاریخ', 'golden-dashboard'); ?></th>
                        <th><?php _e('عملیات', 'golden-dashboard'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tx) :
                        $user = get_userdata($tx->user_id);
                        $status_class = isset($tx->status) ? $tx->status : 'completed';
                        $status_text = isset($status_labels[$status_class]) ? $status_labels[$status_class] : $status_class;
                        $status_badge_class = isset($status_classes[$status_class]) ? $status_classes[$status_class] : '';
                        $fee_amount = isset($tx->fee_amount) ? $tx->fee_amount : 0;
                        $net_amount = isset($tx->net_amount) ? $tx->net_amount : $tx->amount;
                    ?>
                        <tr>
                            <td style="width:60px;"><?php echo $tx->id; ?></td>
                            <td><?php echo $user ? esc_html($user->display_name) : __('نامشخص', 'golden-dashboard'); ?></td>
                            <td>
                                <?php if ($tx->type === 'credit') : ?>
                                    <span style="color:#16a34a;"><?php _e('واریز', 'golden-dashboard'); ?></span>
                                <?php else : ?>
                                    <span style="color:#dc2626;"><?php _e('برداشت', 'golden-dashboard'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo gdb_price($tx->amount); ?></td>
                            <td><?php echo $fee_amount > 0 ? gdb_price($fee_amount) : '-'; ?></td>
                            <td><strong><?php echo gdb_price($net_amount); ?></strong></td>
                            <td>
                                <span class="gdb-status-badge <?php echo esc_attr($status_badge_class); ?>">
                                    <?php echo esc_html($status_text); ?>
                                </span>
                            </td>
                            <td style="white-space:nowrap;"><code><?php echo esc_html($tx->tracking_code); ?></code></td>
                            <td><?php echo esc_html(gdb_date_jalali($tx->created_at, true)); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=gdb-transaction-detail&id=' . $tx->id); ?>" class="button button-secondary button-small">
                                    <?php _e('جزئیات', 'golden-dashboard'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                $base_url = admin_url('admin.php?page=gdb-transactions-history');
                echo paginate_links([
                    'base'    => add_query_arg('paged', '%#%', $base_url),
                    'format'  => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'   => $pages,
                    'current' => $page,
                ]);
                ?>
            </div>
        </div>
    <?php else : ?>
        <p><?php _e('هیچ تراکنشی یافت نشد.', 'golden-dashboard'); ?></p>
    <?php endif; ?>
</div>

<style>
    @media print {
        #adminmenuwrap, #adminmenuback, #wpadminbar, .notice, .button, form, .tablenav { display: none !important; }
        #wpcontent { margin: 0 !important; padding: 20px !important; }
        .gdb-status-badge { border: 1px solid #ddd; }
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
</style>
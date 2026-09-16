<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'golden-dashboard'));
}




$status    = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? gdb_normalize_admin_date_input(sanitize_text_field($_GET['date_from'])) : '';
$date_to   = isset($_GET['date_to']) ? gdb_normalize_admin_date_input(sanitize_text_field($_GET['date_to'])) : '';
$search    = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$filter_user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;

$query = [];
if ($status)    $query['status']    = $status;
if ($date_from) $query['date_from'] = $date_from;
if ($date_to)   $query['date_to']   = $date_to;
if ($search !== '') $query['search'] = $search;
if ($filter_user_id) $query['user_id'] = $filter_user_id;

$per_page = 20;
$page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$data = GDB_Withdraw_Request::get_requests($query, $per_page, $page);




function gdb_admin_render_requests_table($requests, $total, $pages, $page) {
    if ($requests) :
        ?>
        <table class="wp-list-table widefat fixed striped" style="table-layout:fixed; width:100%;">
            <thead>
                <tr>
                    <th style="width:60px;"><?php _e('شناسه', 'golden-dashboard'); ?></th>
                    <th style="width:18%;"><?php _e('کاربر', 'golden-dashboard'); ?></th>
                    <th style="width:12%;"><?php _e('مبلغ درخواستی', 'golden-dashboard'); ?></th>
                    <th style="width:10%;"><?php _e('کارمزد', 'golden-dashboard'); ?></th>
                    <th style="width:12%;"><?php _e('مبلغ قابل واریز', 'golden-dashboard'); ?></th>
                    <th style="width:150px; min-width:150px;"><?php _e('کد پیگیری', 'golden-dashboard'); ?></th>
                    <th style="width:10%;"><?php _e('وضعیت', 'golden-dashboard'); ?></th>
                    <th style="width:13%;"><?php _e('تاریخ درخواست', 'golden-dashboard'); ?></th>
                    <th style="width:10%;"><?php _e('عملیات', 'golden-dashboard'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req) :
                    $user = get_userdata($req->user_id);
                    $status_meta = gdb_admin_get_status_meta($req->status);
                    $fee_amount = isset($req->fee_amount) ? $req->fee_amount : 0;
                    $net_amount = isset($req->net_amount) ? $req->net_amount : $req->amount;
                    ?>
                    <tr>
                        <td style="width:60px;"><?php echo $req->id; ?></td>
                        <td style="width:18%;"><?php echo $user ? $user->display_name . ' (#' . $user->ID . ')' : __('نامشخص', 'golden-dashboard'); ?></td>
                        <td style="width:12%;"><?php echo gdb_price($req->amount); ?></td>
                        <td style="width:10%;"><?php echo $fee_amount > 0 ? gdb_price($fee_amount) : '-'; ?></td>
                        <td style="width:12%;"><strong><?php echo gdb_price($net_amount); ?></strong></td>
                        <td style="width:150px; min-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            <code><?php echo esc_html($req->tracking_code); ?></code>
                        </td>
                        <td style="width:10%;"><span class="gdb-status-badge gdb-status-<?php echo esc_attr($status_meta[0]); ?>"><?php echo esc_html($status_meta[1]); ?></span></td>
                        <td style="width:13%;"><?php echo esc_html(gdb_date_jalali($req->created_at, true)); ?></td>
                        <td style="width:10%;">
                            <a href="<?php echo admin_url('admin.php?page=gdb-transaction-detail&id=' . $req->id); ?>" class="button button-secondary button-small">
                                <?php _e('جزئیات', 'golden-dashboard'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                $base_url = admin_url('admin.php?page=gdb-withdraw-requests');
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
        <p><?php _e('هیچ درخواستی یافت نشد.', 'golden-dashboard'); ?></p>
    <?php endif;
}




function gdb_admin_get_status_meta($status) {
    $map = [
        'pending'    => ['pending', __('در انتظار', 'golden-dashboard')],
        'processing' => ['processing', __('در حال پردازش', 'golden-dashboard')],
        'completed'  => ['completed', __('تکمیل شده', 'golden-dashboard')],
        'on-hold'    => ['on-hold', __('در انتظار', 'golden-dashboard')],
        'cancelled'  => ['cancelled', __('لغو شده', 'golden-dashboard')],
        'refunded'   => ['refunded', __('بازگشت وجه', 'golden-dashboard')],
        'failed'     => ['failed', __('ناموفق', 'golden-dashboard')],
        'rejected'   => ['rejected', __('رد شده', 'golden-dashboard')],
    ];
    return isset($map[$status]) ? $map[$status] : ['default', $status];
}

?>
<div class="wrap">
    <h1><?php _e('درخواست‌های برداشت کیف پول', 'golden-dashboard'); ?></h1>

    <?php if (isset($_GET['gdb_error'])) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html(wp_unslash($_GET['gdb_error'])); ?></p>
        </div>
    <?php elseif (isset($_GET['message']) && $_GET['message'] === 'approved') : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('درخواست برداشت با موفقیت تایید شد.', 'golden-dashboard'); ?></p>
        </div>
    <?php elseif (isset($_GET['message']) && $_GET['message'] === 'rejected') : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('درخواست برداشت رد شد و مبلغ به کیف پول کاربر بازگردانده شد.', 'golden-dashboard'); ?></p>
        </div>
    <?php endif; ?>

    <div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
        <a href="<?php echo admin_url('admin-post.php?action=gdb_export_withdraw_requests_csv&' . http_build_query($_GET)); ?>" class="button button-primary">
            <span class="dashicons dashicons-download" style="margin-top:4px;"></span> <?php _e('خروجی CSV', 'golden-dashboard'); ?>
        </a>
        <button type="button" class="button" onclick="window.print();">
            <span class="dashicons dashicons-printer" style="margin-top:4px;"></span> <?php _e('چاپ', 'golden-dashboard'); ?>
        </button>
    </div>

    <form method="get" action="" style="margin-bottom:20px; background:#f8fafc; padding:15px; border-radius:8px; display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
        <input type="hidden" name="page" value="gdb-withdraw-requests">


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

        <input type="text" class="date-picker" autocomplete="off" name="date_from" value="<?php echo esc_attr(gdb_display_admin_date_input($date_from)); ?>" placeholder="<?php esc_attr_e('از تاریخ', 'golden-dashboard'); ?>">
        <input type="text" class="date-picker" autocomplete="off" name="date_to" value="<?php echo esc_attr(gdb_display_admin_date_input($date_to)); ?>" placeholder="<?php esc_attr_e('تا تاریخ', 'golden-dashboard'); ?>">

        <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('جستجوی کاربر...', 'golden-dashboard'); ?>" style="min-width:150px;">

        <button type="submit" class="button"><?php _e('فیلتر', 'golden-dashboard'); ?></button>
        <a href="<?php echo admin_url('admin.php?page=gdb-withdraw-requests'); ?>" class="button"><?php _e('بازنشانی', 'golden-dashboard'); ?></a>
    </form>

    <div id="gdb-requests-table-wrap">
        <?php
        if ($data['items']) {
            gdb_admin_render_requests_table($data['items'], $data['total'], $data['pages'], $page);
        } else {
            echo '<p>' . __('هیچ درخواستی یافت نشد.', 'golden-dashboard') . '</p>';
        }
        ?>
    </div>
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
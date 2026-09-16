<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'golden-dashboard'));
}

global $wpdb;
$table = $wpdb->prefix . 'gd_wallet_security_log';

$severity  = isset($_GET['severity']) ? sanitize_text_field($_GET['severity']) : '';
$event_type = isset($_GET['event_type']) ? sanitize_text_field($_GET['event_type']) : '';
$date_from = isset($_GET['date_from']) ? gdb_normalize_admin_date_input(sanitize_text_field($_GET['date_from'])) : '';
$date_to   = isset($_GET['date_to']) ? gdb_normalize_admin_date_input(sanitize_text_field($_GET['date_to'])) : '';
$search    = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

$per_page = 50;
$page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($page - 1) * $per_page;

$where = ['1=1'];
$params = [];

if ($severity && in_array($severity, ['low', 'medium', 'high', 'critical'])) {
    $where[] = 'severity = %s';
    $params[] = $severity;
}
if ($event_type) {
    $where[] = 'event_type = %s';
    $params[] = $event_type;
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
    $where[] = '(message LIKE %s OR ip_address LIKE %s OR user_id IN (SELECT ID FROM ' . $wpdb->users . ' WHERE display_name LIKE %s OR user_login LIKE %s))';
    $search_like = '%' . $wpdb->esc_like($search) . '%';
    $params = array_merge($params, [$search_like, $search_like, $search_like, $search_like]);
}

$where_sql = implode(' AND ', $where);
$count_sql = $params ? $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", $params) : "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
$total = (int) $wpdb->get_var($count_sql);

$sql_params = array_merge($params, [$per_page, $offset]);
$sql = $wpdb->prepare("SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d", $sql_params);
$logs = $wpdb->get_results($sql);
$pages = ceil($total / $per_page);

$event_types = $wpdb->get_col("SELECT DISTINCT event_type FROM {$table} ORDER BY event_type ASC");

$severity_labels = [
    'low'      => __('عادی', 'golden-dashboard'),
    'medium'   => __('متوسط', 'golden-dashboard'),
    'high'     => __('خطرناک', 'golden-dashboard'),
    'critical' => __('بحرانی', 'golden-dashboard'),
];
$severity_classes = [
    'low'      => 'gdb-sev-low',
    'medium'   => 'gdb-sev-medium',
    'high'     => 'gdb-sev-high',
    'critical' => 'gdb-sev-critical',
];
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('لاگ امنیتی کیف پول', 'golden-dashboard'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-settings&tab=security')); ?>" class="page-title-action"><?php _e('بازگشت به تنظیمات امنیتی', 'golden-dashboard'); ?></a>
    <hr class="wp-header-end">

    <form method="get" action="" style="margin:20px 0; background:#f8fafc; padding:15px; border-radius:8px; display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
        <input type="hidden" name="page" value="gdb-security-log">

        <select name="severity">
            <option value=""><?php _e('همه سطوح', 'golden-dashboard'); ?></option>
            <?php foreach ($severity_labels as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($severity, $key); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>

        <select name="event_type">
            <option value=""><?php _e('همه رویدادها', 'golden-dashboard'); ?></option>
            <?php foreach ($event_types as $et) : ?>
                <option value="<?php echo esc_attr($et); ?>" <?php selected($event_type, $et); ?>><?php echo esc_html($et); ?></option>
            <?php endforeach; ?>
        </select>

        <input type="text" class="date-picker" autocomplete="off" name="date_from" value="<?php echo esc_attr(gdb_display_admin_date_input($date_from)); ?>" placeholder="<?php esc_attr_e('از تاریخ', 'golden-dashboard'); ?>">
        <input type="text" class="date-picker" autocomplete="off" name="date_to" value="<?php echo esc_attr(gdb_display_admin_date_input($date_to)); ?>" placeholder="<?php esc_attr_e('تا تاریخ', 'golden-dashboard'); ?>">

        <input type="text" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('جستجوی کاربر / پیام / IP...', 'golden-dashboard'); ?>" style="min-width:180px;">

        <button type="submit" class="button"><?php _e('فیلتر', 'golden-dashboard'); ?></button>
        <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-security-log')); ?>" class="button"><?php _e('بازنشانی', 'golden-dashboard'); ?></a>
    </form>

    <?php if ($logs) : ?>
        <div style="overflow-x:auto;">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:60px;"><?php _e('شناسه', 'golden-dashboard'); ?></th>
                        <th><?php _e('کاربر', 'golden-dashboard'); ?></th>
                        <th><?php _e('نوع رویداد', 'golden-dashboard'); ?></th>
                        <th><?php _e('سطح', 'golden-dashboard'); ?></th>
                        <th><?php _e('پیام', 'golden-dashboard'); ?></th>
                        <th><?php _e('IP', 'golden-dashboard'); ?></th>
                        <th><?php _e('تاریخ', 'golden-dashboard'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) :
                        $user = $log->user_id ? get_userdata($log->user_id) : null;
                        $sev = $log->severity ?: 'low';
                        $sev_label = $severity_labels[$sev] ?? $sev;
                        $sev_class = $severity_classes[$sev] ?? '';
                    ?>
                        <tr>
                            <td><?php echo (int) $log->id; ?></td>
                            <td><?php echo $user ? esc_html($user->display_name) : ($log->user_id ? '#' . (int) $log->user_id : '-'); ?></td>
                            <td><code><?php echo esc_html($log->event_type); ?></code></td>
                            <td><span class="gdb-sev-badge <?php echo esc_attr($sev_class); ?>"><?php echo esc_html($sev_label); ?></span></td>
                            <td><?php echo esc_html($log->message); ?></td>
                            <td><code><?php echo esc_html($log->ip_address); ?></code></td>
                            <td><?php echo esc_html(function_exists('gdb_date_jalali') ? gdb_date_jalali($log->created_at, true) : $log->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                $base_url = admin_url('admin.php?page=gdb-security-log');
                echo paginate_links([
                    'base'      => add_query_arg('paged', '%#%', $base_url),
                    'format'    => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'     => $pages,
                    'current'   => $page,
                ]);
                ?>
            </div>
        </div>
    <?php else : ?>
        <p><?php _e('هیچ رویدادی یافت نشد.', 'golden-dashboard'); ?></p>
    <?php endif; ?>
</div>

<style>
    .gdb-sev-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .gdb-sev-low { background: #e5e7eb; color: #4b5563; }
    .gdb-sev-medium { background: #fef3c7; color: #d97706; }
    .gdb-sev-high { background: #ffe5d0; color: #ff9800; }
    .gdb-sev-critical { background: #fee2e2; color: #dc3545; }
</style>

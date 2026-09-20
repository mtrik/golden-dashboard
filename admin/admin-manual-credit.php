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

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- This block only reads GET parameters to display a status banner and filter/paginate a read-only transaction list; no data is written or changed here.
$message = '';
$message_type = '';
if (isset($_GET['message'])) {
    switch (sanitize_text_field(wp_unslash($_GET['message']))) {
        case 'success':
            $message = __('تراکنش با موفقیت ثبت شد.', 'golden-dashboard');
            $message_type = 'success';
            break;
        case 'error':
            $message = __('خطا در ثبت تراکنش. لطفاً دوباره تلاش کنید.', 'golden-dashboard');
            $message_type = 'error';
            break;
        default:
            $message = '';
    }
}

$selected_user_id = isset($_GET['user_id']) ? absint(wp_unslash($_GET['user_id'])) : 0;
$selected_user = $selected_user_id ? get_userdata($selected_user_id) : false;
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$user_fee_summary = null;
if ($selected_user_id) {
    global $wpdb;
    $user_fee_summary = $wpdb->get_row($wpdb->prepare(
        "SELECT
            COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0) as total_withdrawn,
            COALESCE(SUM(fee_amount), 0) as total_fee,
            COALESCE(SUM(net_amount), 0) as total_net
         FROM {$wpdb->prefix}gd_wallet_transactions
         WHERE user_id = %d AND status = 'completed'",
        $selected_user_id
    ));
}

$currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '';

$transactions = [];
$total = 0;
$pages = 1;
$current_page = 1;
$per_page = 20;

if ($selected_user_id) {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filtering/pagination of an already-selected user's transaction list; no data is written or changed here.
    $tx_type = isset($_GET['tx_type']) ? sanitize_text_field(wp_unslash($_GET['tx_type'])) : '';
    $tx_status = isset($_GET['tx_status']) ? sanitize_text_field(wp_unslash($_GET['tx_status'])) : '';
    $tx_date_from = isset($_GET['tx_date_from']) ? gdb_normalize_admin_date_input(sanitize_text_field(wp_unslash($_GET['tx_date_from']))) : '';
    $tx_date_to = isset($_GET['tx_date_to']) ? gdb_normalize_admin_date_input(sanitize_text_field(wp_unslash($_GET['tx_date_to']))) : '';
    $current_page = isset($_GET['tx_paged']) ? max(1, absint(wp_unslash($_GET['tx_paged']))) : 1;
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    global $wpdb;
    $table = $wpdb->prefix . 'gd_wallet_transactions';
    $where = ['user_id = %d'];
    $params = [$selected_user_id];

    if ($tx_type && in_array($tx_type, ['credit', 'debit'])) {
        $where[] = 'type = %s';
        $params[] = $tx_type;
    }
    if ($tx_status) {
        $where[] = 'status = %s';
        $params[] = $tx_status;
    }
    if ($tx_date_from) {
        $where[] = 'DATE(created_at) >= %s';
        $params[] = $tx_date_from;
    }
    if ($tx_date_to) {
        $where[] = 'DATE(created_at) <= %s';
        $params[] = $tx_date_to;
    }

    $where_sql = implode(' AND ', $where);
    // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $where_sql/$params are built dynamically from a fixed set of %d/%s placeholders always pushed together in the same order and count; manually verified to match at every branch.
    $count_sql = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", $params);
    $total = (int) $wpdb->get_var($count_sql);

    if ($total > 0) {
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;
        // phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- $where_sql/$params counts always match (see above); combined with the 2 literal LIMIT/OFFSET placeholders and array_merge($params, [$per_page, $offset]), counts always match. Manually verified.
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d",
            array_merge($params, [$per_page, $offset])
        );
        // phpcs:enable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
        $transactions = $wpdb->get_results($sql);
        $pages = ceil($total / $per_page);
    }
}

// phpcs:disable WordPress.Security.NonceVerification.Missing -- Read-only re-display of previously submitted form values after a validation error (sticky form fields); no state is changed here, and the actual submission is verified with its own nonce in the admin-post.php handler.
$sticky_transaction_type = isset($_POST['transaction_type']) ? sanitize_text_field(wp_unslash($_POST['transaction_type'])) : '';
$sticky_amount           = isset($_POST['amount']) ? sanitize_text_field(wp_unslash($_POST['amount'])) : '';
$sticky_fee_type         = isset($_POST['fee_type']) ? sanitize_text_field(wp_unslash($_POST['fee_type'])) : '';
$sticky_fee_value        = isset($_POST['fee_value']) ? sanitize_text_field(wp_unslash($_POST['fee_value'])) : '';
$sticky_transaction_date = isset($_POST['transaction_date']) ? sanitize_text_field(wp_unslash($_POST['transaction_date'])) : '';
$sticky_description      = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
// phpcs:enable WordPress.Security.NonceVerification.Missing
?>
<div class="wrap">
    <h1><?php esc_html_e('شارژ دستی کیف پول', 'golden-dashboard'); ?></h1>

    <?php if ($message) : ?>
        <div class="notice notice-<?php echo esc_attr($message_type); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <div class="gdb-detail-card">
        <div class="gdb-detail-card-title">
            <?php esc_html_e('ثبت تراکنش دستی', 'golden-dashboard'); ?>
        </div>
        <div class="gdb-detail-card-body">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('gdb_manual_credit_action', 'gdb_manual_credit_nonce'); ?>
                <input type="hidden" name="action" value="gdb_manual_credit">
                <input type="hidden" name="gdb_return_url" value="<?php echo esc_url(remove_query_arg(['message', 'gdb_error', 'edit_tx'])); ?>">

                <table class="gdb-detail-table">
                    <tr>
                        <th><label for="user_search"><?php esc_html_e('جستجوی کاربر', 'golden-dashboard'); ?></label></th>
                        <td>
                            <select id="user_search" name="user_id" style="width: 100%; max-width: 400px;">
                                <?php if ($selected_user) : ?>
                                    <option value="<?php echo esc_attr($selected_user->ID); ?>" selected>
                                        <?php echo esc_html($selected_user->display_name . ' (' . $selected_user->user_email . ')'); ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                            <p class="description"><?php esc_html_e('نام، نام کاربری یا ایمیل کاربر را وارد کنید.', 'golden-dashboard'); ?></p>
                        </td>
                    </tr>
                    <?php if ($selected_user) : ?>
                        <tr>
                            <th><?php esc_html_e('اطلاعات کاربر', 'golden-dashboard'); ?></th>
                            <td>
                                <strong><?php echo esc_html($selected_user->display_name); ?></strong><br>
                                <?php echo esc_html($selected_user->user_email); ?><br>
                                <?php esc_html_e('موجودی فعلی:', 'golden-dashboard'); ?> <?php echo wp_kses_post(gdb_price(GDB_Wallet::balance($selected_user->ID))); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th><?php esc_html_e('نوع تراکنش', 'golden-dashboard'); ?></th>
                        <td>
                            <label><input type="radio" name="transaction_type" value="credit" <?php checked($sticky_transaction_type === 'credit', true); ?>> <?php esc_html_e('افزایش موجودی (شارژ)', 'golden-dashboard'); ?></label><br>
                            <label><input type="radio" name="transaction_type" value="debit" <?php checked($sticky_transaction_type === 'debit', true); ?>> <?php esc_html_e('کاهش موجودی (برداشت)', 'golden-dashboard'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="amount">
                                <?php esc_html_e('مبلغ', 'golden-dashboard'); ?>
                                <?php if ($currency_symbol) : ?>
                                    <span style="font-weight: normal; color: #666;">(<?php echo esc_html($currency_symbol); ?>)</span>
                                <?php endif; ?>
                            </label>
                        </th>
                        <td>
                            <input type="number" name="amount" id="amount" step="any" min="0" value="<?php echo esc_attr($sticky_amount); ?>" style="width: 200px;">
                            <p class="description"><?php esc_html_e('مبلغ را بر اساس واحد پول سایت وارد کنید (تبدیل خودکار انجام می‌شود).', 'golden-dashboard'); ?></p>
                        </td>
                    </tr>
                    <tr id="gdb-fee-row" style="display:none;">
                        <th><?php esc_html_e('کارمزد (فقط برداشت)', 'golden-dashboard'); ?></th>
                        <td>
                            <label><input type="radio" name="fee_type" value="percent" <?php checked($sticky_fee_type === '' || $sticky_fee_type === 'percent', true); ?>> <?php esc_html_e('درصدی (٪)', 'golden-dashboard'); ?></label>
                            <label style="margin-right:15px;"><input type="radio" name="fee_type" value="fixed" <?php checked($sticky_fee_type === 'fixed', true); ?>> <?php esc_html_e('مبلغ ثابت', 'golden-dashboard'); ?></label>
                            <br>
                            <input type="number" name="fee_value" id="fee_value" step="any" min="0" value="<?php echo esc_attr($sticky_fee_value); ?>" style="width: 200px; margin-top:6px;">
                            <p class="description"><?php esc_html_e('در حالت درصدی، عددی بین ۰ تا ۱۰۰ وارد کنید؛ در حالت مبلغ ثابت، مبلغ کارمزد را بر اساس واحد پول سایت وارد کنید. کارمزد از مبلغ برداشت‌شده کم می‌شود و «مبلغ قابل واریز» در صفحه‌ی جزئیات تراکنش نشان داده خواهد شد.', 'golden-dashboard'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="transaction_date"><?php esc_html_e('تاریخ تراکنش', 'golden-dashboard'); ?></label></th>
                        <td>
                            <input type="text" class="date-picker" autocomplete="off" name="transaction_date" id="transaction_date" value="<?php echo $sticky_transaction_date ? esc_attr($sticky_transaction_date) : esc_attr(gdb_display_admin_date_input(gmdate('Y-m-d', current_time('timestamp')))); ?>" style="width: 200px;">
                            <p class="description"><?php esc_html_e('پیش‌فرض امروز است؛ برای ثبت اسناد مربوط به روزهای قبل، تاریخ را تغییر دهید.', 'golden-dashboard'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="description"><?php esc_html_e('توضیحات (اختیاری)', 'golden-dashboard'); ?></label></th>
                        <td>
                            <textarea name="description" id="description" rows="3" style="width: 100%; max-width: 400px;"><?php echo esc_textarea($sticky_description); ?></textarea>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php esc_html_e('ثبت تراکنش', 'golden-dashboard'); ?></button>
                </p>
            </form>
        </div>
    </div>

    <?php if ($selected_user && $user_fee_summary) : ?>
        <div class="gdb-detail-card" style="margin-top: 20px;">
            <div class="gdb-detail-card-title">
                <?php
                /* translators: %s: user display name */
                echo esc_html(sprintf(__('خلاصه‌ی تاریخچه‌ی %s', 'golden-dashboard'), $selected_user->display_name));
                ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-fee-report&user_id=' . $selected_user_id)); ?>" class="button button-small" style="float:left;"><?php esc_html_e('گزارش کامل کارمزد', 'golden-dashboard'); ?></a>
            </div>
            <div class="gdb-detail-card-body">
                <div class="gdb-dashboard-grid">
                    <div class="gdb-dashboard-card">
                        <h3><?php esc_html_e('مجموع برداشت (ناخالص)', 'golden-dashboard'); ?></h3>
                        <span class="gdb-stat-number gdb-color-red"><?php echo esc_html(gdb_price_localized($user_fee_summary->total_withdrawn)); ?></span>
                    </div>
                    <div class="gdb-dashboard-card">
                        <h3><?php esc_html_e('مجموع کارمزد پرداختی', 'golden-dashboard'); ?></h3>
                        <span class="gdb-stat-number gdb-color-warning"><?php echo esc_html(gdb_price_localized($user_fee_summary->total_fee)); ?></span>
                    </div>
                    <div class="gdb-dashboard-card">
                        <h3><?php esc_html_e('مجموع خالص دریافتی', 'golden-dashboard'); ?></h3>
                        <span class="gdb-stat-number gdb-color-green"><?php echo esc_html(gdb_price_localized($user_fee_summary->total_net)); ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($selected_user_id) : ?>
        <div class="gdb-detail-card" style="margin-top: 20px;">
            <div class="gdb-detail-card-title">
                <?php
                /* translators: %s: user display name */
                echo esc_html(sprintf(__('تراکنش‌های کاربر: %s', 'golden-dashboard'), $selected_user->display_name));
                ?>
                <span style="font-weight: normal; font-size: 13px; color: #6b7280;">
                    (<?php echo esc_html(number_format_i18n($total)); ?> <?php esc_html_e('تراکنش', 'golden-dashboard'); ?>)
                </span>
                <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-transactions-history&search=' . urlencode($selected_user->display_name))); ?>" class="button button-small" style="float: left;">
                    <?php esc_html_e('مشاهده همه در تاریخچه', 'golden-dashboard'); ?>
                </a>
            </div>
            <div class="gdb-detail-card-body">
                <form method="get" action="" style="margin-bottom:20px; background:#f8fafc; padding:15px; border-radius:8px; display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                    <input type="hidden" name="page" value="gdb-manual-credit">
                    <input type="hidden" name="user_id" value="<?php echo esc_attr($selected_user_id); ?>">

                    <?php // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only display of the current GET filter state in a filter form; no data is written or changed here. ?>
                    <select name="tx_type">
                        <option value=""><?php esc_html_e('همه انواع', 'golden-dashboard'); ?></option>
                        <option value="credit" <?php selected(isset($_GET['tx_type']) && sanitize_key(wp_unslash($_GET['tx_type'])) === 'credit'); ?>><?php esc_html_e('واریز', 'golden-dashboard'); ?></option>
                        <option value="debit" <?php selected(isset($_GET['tx_type']) && sanitize_key(wp_unslash($_GET['tx_type'])) === 'debit'); ?>><?php esc_html_e('برداشت', 'golden-dashboard'); ?></option>
                    </select>

                    <select name="tx_status">
                        <option value=""><?php esc_html_e('همه وضعیت‌ها', 'golden-dashboard'); ?></option>
                        <option value="pending" <?php selected(isset($_GET['tx_status']) && sanitize_key(wp_unslash($_GET['tx_status'])) === 'pending'); ?>><?php esc_html_e('در انتظار', 'golden-dashboard'); ?></option>
                        <option value="completed" <?php selected(isset($_GET['tx_status']) && sanitize_key(wp_unslash($_GET['tx_status'])) === 'completed'); ?>><?php esc_html_e('تکمیل شده', 'golden-dashboard'); ?></option>
                        <option value="rejected" <?php selected(isset($_GET['tx_status']) && sanitize_key(wp_unslash($_GET['tx_status'])) === 'rejected'); ?>><?php esc_html_e('رد شده', 'golden-dashboard'); ?></option>
                        <option value="cancelled" <?php selected(isset($_GET['tx_status']) && sanitize_key(wp_unslash($_GET['tx_status'])) === 'cancelled'); ?>><?php esc_html_e('لغو شده', 'golden-dashboard'); ?></option>
                        <option value="refunded" <?php selected(isset($_GET['tx_status']) && sanitize_key(wp_unslash($_GET['tx_status'])) === 'refunded'); ?>><?php esc_html_e('بازگشت وجه', 'golden-dashboard'); ?></option>
                    </select>

                    <input type="text" class="date-picker" autocomplete="off" name="tx_date_from" value="<?php echo isset($_GET['tx_date_from']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['tx_date_from']))) : ''; ?>" placeholder="<?php esc_html_e('از تاریخ', 'golden-dashboard'); ?>">
                    <input type="text" class="date-picker" autocomplete="off" name="tx_date_to" value="<?php echo isset($_GET['tx_date_to']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['tx_date_to']))) : ''; ?>" placeholder="<?php esc_html_e('تا تاریخ', 'golden-dashboard'); ?>">
                    <?php // phpcs:enable WordPress.Security.NonceVerification.Recommended ?>

                    <button type="submit" class="button"><?php esc_html_e('فیلتر', 'golden-dashboard'); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=gdb-manual-credit&user_id=' . $selected_user_id)); ?>" class="button"><?php esc_html_e('بازنشانی', 'golden-dashboard'); ?></a>
                </form>

                <?php if ($transactions) : ?>
                    <?php
                    
                    
                    
                    
                    
                    
                    
                    
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
                    <div style="overflow-x:auto;">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width:50px;"><?php esc_html_e('ردیف', 'golden-dashboard'); ?></th>
                                    <th><?php esc_html_e('تاریخ', 'golden-dashboard'); ?></th>
                                    <th><?php esc_html_e('نوع تراکنش', 'golden-dashboard'); ?></th>
                                    <th><?php esc_html_e('مبلغ', 'golden-dashboard'); ?></th>
                                    <th><?php esc_html_e('کارمزد', 'golden-dashboard'); ?></th>
                                    <th><?php esc_html_e('موجودی قبل', 'golden-dashboard'); ?></th>
                                    <th><?php esc_html_e('موجودی بعد', 'golden-dashboard'); ?></th>
                                    <th><?php esc_html_e('وضعیت', 'golden-dashboard'); ?></th>
                                    <th><?php esc_html_e('توضیحات', 'golden-dashboard'); ?></th>
                                    <th style="width:130px;"><?php esc_html_e('عملیات', 'golden-dashboard'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $row_num = (($current_page - 1) * $per_page) + 1; ?>
                                <?php foreach ($transactions as $tx) :
                                    $is_credit = ($tx->type === 'credit');
                                    $status = isset($tx->status) ? $tx->status : 'completed';
                                    $status_text = isset($status_labels[$status]) ? $status_labels[$status] : $status;
                                    $status_badge_class = isset($status_classes[$status]) ? $status_classes[$status] : '';
                                    $fee_amount = isset($tx->fee_amount) ? $tx->fee_amount : 0;
                                    $balance_before = isset($tx->balance_before) ? $tx->balance_before : 0;
                                    
                                    
                                    
                                    
                                    $is_editable = in_array($tx->transaction_type, ['admin_credit', 'admin_debit']);
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($row_num++); ?></td>
                                        <td><?php echo esc_html(gdb_date_jalali($tx->created_at, true)); ?></td>
                                        <td>
                                            <?php if ($is_credit) : ?>
                                                <span style="color:#16a34a;"><?php echo esc_html(gdb_transaction_label($tx, true)); ?></span>
                                            <?php else : ?>
                                                <span style="color:#dc2626;"><?php echo esc_html(gdb_transaction_label($tx, false)); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo wp_kses_post(gdb_price(abs($tx->amount))); ?></td>
                                        <td><?php echo $fee_amount > 0 ? wp_kses_post(gdb_price($fee_amount)) : '-'; ?></td>
                                        <td><?php echo wp_kses_post(gdb_price($balance_before)); ?></td>
                                        <td><strong><?php echo wp_kses_post(gdb_price($tx->balance_after)); ?></strong></td>
                                        <td>
                                            <span class="gdb-status-badge <?php echo esc_attr($status_badge_class); ?>">
                                                <?php echo esc_html($status_text); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($tx->description); ?></td>
                                        <td>
                                            <?php if ($is_editable) : ?>
                                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;" onsubmit="return confirm('<?php echo esc_js(__('این تراکنش کاملاً و برای همیشه حذف می‌شود و موجودی کاربر بر همین اساس اصلاح خواهد شد. آیا مطمئن هستید؟', 'golden-dashboard')); ?>');">
                                                    <input type="hidden" name="action" value="gdb_delete_manual_transaction">
                                                    <input type="hidden" name="transaction_id" value="<?php echo esc_attr($tx->id); ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo esc_attr($selected_user_id); ?>">
                                                    <input type="hidden" name="gdb_return_url" value="<?php echo esc_url(remove_query_arg(['message', 'gdb_error'])); ?>">
                                                    <?php wp_nonce_field('gdb_delete_manual_transaction_action', 'gdb_delete_manual_transaction_nonce'); ?>
                                                    <button type="submit" class="button button-small" style="color:#dc2626;"><?php esc_html_e('حذف', 'golden-dashboard'); ?></button>
                                                </form>
                                            <?php else : ?>
                                                &mdash;
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($pages > 1) : ?>
                        <div class="tablenav">
                            <div class="tablenav-pages">
                                <?php
                                $base_url = remove_query_arg(['tx_paged']);
                                echo wp_kses_post(paginate_links([
                                    'base'      => add_query_arg('tx_paged', '%#%', $base_url),
                                    'format'    => '',
                                    'prev_text' => '&laquo;',
                                    'next_text' => '&raquo;',
                                    'total'     => $pages,
                                    'current'   => $current_page,
                                ]));
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <p><?php esc_html_e('هیچ تراکنشی برای این کاربر یافت نشد.', 'golden-dashboard'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    function gdbToggleFeeRow() {
        var isDebit = $('input[name="transaction_type"]:checked').val() === 'debit';
        $('#gdb-fee-row').toggle(isDebit);
    }
    gdbToggleFeeRow();
    $('input[name="transaction_type"]').on('change', gdbToggleFeeRow);

    $('#user_search').select2({
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
                return {
                    results: data.data,
                    pagination: {
                        more: data.more
                    }
                };
            },
            cache: true
        },
        minimumInputLength: 2,
        placeholder: '<?php esc_html_e('نام کاربر را وارد کنید...', 'golden-dashboard'); ?>',
        allowClear: true,
        language: 'fa',
        dir: 'rtl',
        templateResult: function(user) {
            if (user.loading) return user.text;
            return user.display_name + ' (' + user.user_email + ')';
        },
        templateSelection: function(user) {
            return user.display_name || user.text;
        }
    }).on('select2:select', function(e) {
        var userId = e.params.data.id;
        if (userId) {
            var url = new URL(window.location.href);
            url.searchParams.set('user_id', userId);
            url.searchParams.delete('tx_type');
            url.searchParams.delete('tx_status');
            url.searchParams.delete('tx_date_from');
            url.searchParams.delete('tx_date_to');
            url.searchParams.delete('tx_paged');
            window.location.href = url.toString();
        }
    });
});
</script>

<style>
    .gdb-history-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }
    .gdb-history-badge.gdb-status-pending { background: #fef3c7; color: #d97706; }
    .gdb-history-badge.gdb-status-processing { background: #dbeafe; color: #2563eb; }
    .gdb-history-badge.gdb-status-completed { background: #dcfce7; color: #16a34a; }
    .gdb-history-badge.gdb-status-on-hold { background: #f3e8ff; color: #9333ea; }
    .gdb-history-badge.gdb-status-cancelled { background: #e5e7eb; color: #6b7280; }
    .gdb-history-badge.gdb-status-refunded { background: #fef2f2; color: #dc2626; }
    .gdb-history-badge.gdb-status-failed { background: #fee2e2; color: #dc2626; }
    .gdb-history-badge.gdb-status-rejected { background: #fee2e2; color: #dc2626; }

    .gdb-history-amount {
        display: inline-block !important;
        white-space: nowrap !important;
        direction: ltr !important;
        unicode-bidi: embed !important;
    }
    .gdb-amount-sign {
        display: inline-block !important;
        margin-left: 1px !important;
        margin-right: 1px !important;
    }

    .gdb-history-table th,
    .gdb-history-table td {
        text-align: center !important;
        vertical-align: middle !important;
    }
    .gdb-history-table .gdb-history-badge {
        display: inline-block;
        white-space: nowrap;
    }

    @media (max-width: 782px) {
        .gdb-history-table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        .gdb-detail-table th,
        .gdb-detail-table td {
            display: block;
            width: 100% !important;
            padding: 6px 8px !important;
        }
        .gdb-detail-table th {
            font-weight: 700;
            background: #f9fafb;
            border-bottom: 1px solid #eee !important;
        }
        .gdb-detail-table td {
            border-bottom: 1px solid #f3f4f6 !important;
        }
        .gdb-detail-table tr {
            margin-bottom: 10px;
            display: block;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        .gdb-detail-card-title .button {
            float: none !important;
            display: inline-block;
            margin-top: 5px;
        }
    }
    @media (max-width: 480px) {
        .gdb-history-table th,
        .gdb-history-table td {
            padding: 4px 6px !important;
            font-size: 12px;
        }
        .gdb-history-badge {
            font-size: 10px !important;
            padding: 2px 8px !important;
        }
        .gdb-history-amount {
            font-size: 12px !important;
        }
    }
</style>
<?php // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange ?>

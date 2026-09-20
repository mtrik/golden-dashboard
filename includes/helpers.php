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

if (!function_exists('gdb_display_amount')) {
    function gdb_display_amount($amount) {
        $amount = (float) $amount;
        if (function_exists('get_woocommerce_currency') && get_woocommerce_currency() === 'IRR') {
            return $amount * 10;
        }
        return $amount;
    }
}

if (!function_exists('gdb_storage_amount')) {
    function gdb_storage_amount($amount) {
        $amount = (float) $amount;
        if (function_exists('get_woocommerce_currency') && get_woocommerce_currency() === 'IRR') {
            return $amount / 10;
        }
        return $amount;
    }
}

if (!function_exists('gdb_price')) {

    function gdb_price($amount = 0)
    {
        $amount = gdb_display_amount((float) $amount);
        return wc_price($amount);
    }

}

if (!function_exists('gdb_price_plain')) {

    function gdb_price_plain($amount = 0)
    {
        $amount = gdb_display_amount((float) $amount);
        
        
        
        
        
        $currency_symbol = html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8');
        $price = number_format($amount, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator());
        $position = get_option('woocommerce_currency_pos', 'left');
        switch ($position) {
            case 'left':
                return $currency_symbol . $price;
            case 'right':
                return $price . $currency_symbol;
            case 'left_space':
                return $currency_symbol . ' ' . $price;
            case 'right_space':
                return $price . ' ' . $currency_symbol;
            default:
                return $currency_symbol . $price;
        }
    }

}

if (!function_exists('gdb_wc_price_plain')) {

    function gdb_wc_price_plain($amount = 0)
    {
        $amount = (float) $amount;
        if (function_exists('wc_price')) {
            return html_entity_decode(wp_strip_all_tags(wc_price($amount)), ENT_QUOTES, 'UTF-8');
        }
        return number_format($amount, 0);
    }

}

if (!function_exists('gdb_get_safe_return_url')) {
    function gdb_get_safe_return_url($posted_field = 'gdb_return_url') {
        $return_url = '';

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Shared helper only used to build a redirect target after the caller has already verified its own action nonce; value is escaped with esc_url_raw() and never used to change state here.
        if (!empty($_POST[$posted_field])) {
            $return_url = esc_url_raw(wp_unslash($_POST[$posted_field]));
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if (!$return_url) {
            $referer = wp_get_referer();
            if ($referer) {
                $return_url = $referer;
            }
        }

        if (!$return_url) {
            $return_url = home_url('/');
        }

        $home_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $target_host = wp_parse_url($return_url, PHP_URL_HOST);
        if (!$target_host || strtolower($target_host) !== strtolower((string) $home_host)) {
            $return_url = home_url('/');
        }

        $return_url = remove_query_arg(['gdb_topup_result', 'gdb_gold_result', 'order_id', 'key', 'gdb_message'], $return_url);

        return $return_url;
    }
}

if (!function_exists('gdb_get_current_url_clean')) {

    function gdb_get_current_url_clean()
    {
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $url = (is_ssl() ? 'https://' : 'http://') . $host . $uri;
        return remove_query_arg(['gdb_topup_result', 'gdb_gold_result', 'order_id', 'key', 'gdb_message'], $url);
    }

}

if (!function_exists('gdb_price_localized')) {

    function gdb_price_localized($amount = 0)
    {
        $amount = gdb_display_amount((float) $amount);
        $currency_symbol = get_woocommerce_currency_symbol();
        $price = number_format_i18n($amount, wc_get_price_decimals());
        $position = get_option('woocommerce_currency_pos', 'left');
        switch ($position) {
            case 'left':
                return $currency_symbol . ' ' . $price;
            case 'right':
                return $price . ' ' . $currency_symbol;
            case 'left_space':
                return $currency_symbol . ' ' . $price;
            case 'right_space':
                return $price . ' ' . $currency_symbol;
            default:
                return $currency_symbol . ' ' . $price;
        }
    }

}

if (!function_exists('gdb_format_quantity')) {

    function gdb_format_quantity($value, $max_decimals = 3) {
        $value = (float) $value;
        $trimmed = rtrim(rtrim(number_format($value, $max_decimals, '.', ''), '0'), '.');
        $decimals = (strpos($trimmed, '.') !== false) ? strlen(substr($trimmed, strpos($trimmed, '.') + 1)) : 0;
        return number_format_i18n($value, $decimals);
    }

}

if (!function_exists('gdb_normalize_admin_date_input')) {

    function gdb_normalize_admin_date_input($value) {
        if (empty($value)) {
            return $value;
        }

        if (function_exists('gregdate')) {

            $ascii_value = strtr($value, [
                '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
                '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
                '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            ]);
            $converted = gregdate('Y-m-d', $ascii_value);
            if (!empty($converted) && $converted !== false) {
                return $converted;
            }
        }

        return $value;
    }

}

if (!function_exists('gdb_display_admin_date_input')) {

    function gdb_display_admin_date_input($value) {
        if (empty($value)) {
            return $value;
        }

        if (function_exists('parsidate')) {

            $converted = parsidate('Y-m-d', $value, 'eng');
            if (!empty($converted) && $converted !== false) {
                return $converted;
            }
        }

        return $value;
    }

}

if (!function_exists('gdb_date_jalali')) {

    function gdb_date_jalali($datetime, $with_time = true) {
        if (empty($datetime)) {
            return '';
        }

        
        
        
        
        $date = wc_string_to_datetime($datetime);
        if (!$date) {
            return '';
        }

        $format = get_option('date_format', 'Y-m-d');
        if ($with_time) {
            
            
            
            
            
            $format .= ' H:i:s';
        }

        
        
        
        return wc_format_datetime($date, $format);
    }

}

if (!function_exists('gdb_admin_redirect_error')) {

    function gdb_admin_redirect_error($message, $fallback_url = '') {
        
        
        
        
        
        $target = '';
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Shared helper only used to build a redirect target after the caller has already verified its own action nonce; value is escaped with esc_url_raw() and never used to change state here.
        if (!empty($_POST['gdb_return_url'])) {
            $target = esc_url_raw(wp_unslash($_POST['gdb_return_url']));
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        if (!$target) {
            $target = wp_get_referer();
        }
        if (!$target) {
            $target = $fallback_url ?: admin_url();
        }
        $target = remove_query_arg('gdb_error', $target);
        wp_safe_redirect(add_query_arg('gdb_error', rawurlencode($message), $target));
        exit;
    }

}

if (!function_exists('gdb_get_wc_orders_admin_url')) {

    function gdb_get_wc_orders_admin_url($meta_key = '', $meta_value = '', $statuses = []) {
        $is_hpos = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')
            && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

        $base = $is_hpos
            ? admin_url('admin.php?page=wc-orders')
            : admin_url('edit.php?post_type=shop_order');

        $args = [];
        if ($meta_key && $meta_value) {
            $args['gdb_meta_key'] = $meta_key;
            $args['gdb_meta_value'] = $meta_value;
        }
        if (!empty($statuses)) {
            $args['gdb_statuses'] = implode(',', array_map('sanitize_key', $statuses));
        }

        return add_query_arg($args, $base);
    }

}
if (!function_exists('gdb_empty')) {

    function gdb_empty($text = '')
    {
        if (!$text) {
            $text = __('اطلاعاتی برای نمایش وجود ندارد.', 'golden-dashboard');
        }

        return '<div class="gdb-empty">' . esc_html($text) . '</div>';
    }

}

if (!function_exists('gdb_login_required')) {

    function gdb_login_required()
    {
        return '<div class="gdb-login-required">' .
            esc_html__('برای مشاهده این بخش ابتدا وارد حساب کاربری شوید.', 'golden-dashboard') .
            '</div>';
    }

}

if (!function_exists('gdb_transaction_label')) {

    function gdb_transaction_label($tx, $is_credit)
    {
        $map = [
            'admin_credit'        => __('شارژ توسط مدیر', 'golden-dashboard'),
            'admin_debit'         => __('برداشت توسط مدیر', 'golden-dashboard'),
            'order_payment'       => __('پرداخت سفارش', 'golden-dashboard'),
            'order_refund'        => __('بازگشت وجه', 'golden-dashboard'),
            'withdraw'            => __('برداشت', 'golden-dashboard'),
            'withdraw_request'    => __('درخواست برداشت', 'golden-dashboard'),
            'gold_purchase'       => __('خرید طلا/فلز', 'golden-dashboard'),
            'cashback'            => __('کش‌بک', 'golden-dashboard'),
            'cashback_reversal'   => __('برگشت کش‌بک', 'golden-dashboard'),
            'withdraw_rejected_refund' => __('بازگشت وجه (رد درخواست برداشت)', 'golden-dashboard'),
        ];

        if (isset($map[$tx->transaction_type])) {
            return $map[$tx->transaction_type];
        }

        return $is_credit ? __('واریز', 'golden-dashboard') : __('برداشت', 'golden-dashboard');
    }

}

if (!function_exists('gdb_render_transaction_item')) {

    function gdb_render_transaction_item($tx, $settings = []) {
        $defaults = [
            'show_icon'          => true,
            'show_amount'        => true,
            'show_date'          => true,
            'show_description'   => true,
            'show_balance_after' => false,
            'show_status'        => true,
            'show_admin_info'    => true,
            'show_fee'           => true,
        ];
        $settings = wp_parse_args($settings, $defaults);

        $is_credit    = ($tx->type === 'credit');
        $sign         = $is_credit ? '+' : '−';
        $amount_class = $is_credit ? 'gdb-transaction-amount-credit' : 'gdb-transaction-amount-debit';
        $tx_class     = $is_credit ? 'gdb-transaction-credit' : 'gdb-transaction-debit';
        $label        = gdb_transaction_label($tx, $is_credit);
        $status       = isset($tx->status) ? $tx->status : '';

        $status_display = '';
        if ($settings['show_status'] && in_array($tx->transaction_type, ['withdraw_request', 'order_payment']) && $status) {
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
            if (isset($status_labels[$status])) {
                $status_display = '<span class="gdb-transaction-status ' . esc_attr($status_classes[$status]) . '">' . esc_html($status_labels[$status]) . '</span>';
            }
        }

        $admin_info = '';
        if ($settings['show_admin_info'] && in_array($tx->transaction_type, ['withdraw_request', 'order_payment']) && in_array($status, ['completed', 'rejected', 'cancelled', 'refunded', 'failed'])) {
            $parts = [];
            if (!empty($tx->admin_note)) {
                $parts[] = '<span class="gdb-admin-note">' . wpautop(esc_html($tx->admin_note)) . '</span>';
            }
            if (!empty($tx->bank_transaction_id)) {
                /* translators: %s: bank transaction ID */
                $parts[] = '<span class="gdb-bank-id">' . sprintf(__('شماره تراکنش: %s', 'golden-dashboard'), esc_html($tx->bank_transaction_id)) . '</span>';
            }
            if (!empty($tx->bank_date)) {
                $bank_date_display = gdb_date_jalali($tx->bank_date, false);
                if ($bank_date_display) {
                    /* translators: %s: bank deposit date */
                    $parts[] = '<span class="gdb-bank-date">' . sprintf(__('تاریخ واریز: %s', 'golden-dashboard'), $bank_date_display) . '</span>';
                }
            }
            if ($parts) {
                $admin_info = '<div class="gdb-transaction-admin-info">' . implode(' | ', $parts) . '</div>';
            }
        }

        $fee_display = '';
        if ($settings['show_fee'] && in_array($tx->transaction_type, ['withdraw_request', 'withdraw']) && isset($tx->fee_amount) && $tx->fee_amount > 0) {
            $fee_display = '<div class="gdb-transaction-fee" style="font-size: 12px; color: #6b7280; margin-top: 2px;">' 
                /* translators: 1: fee amount, 2: fee percent */
                . sprintf(__('کارمزد: %1$s (%2$.2f%%)', 'golden-dashboard'), gdb_price($tx->fee_amount), ($tx->fee_amount / $tx->amount * 100)) 
                . '</div>';
        }

        $pending_class = (in_array($status, ['completed', 'refunded']) || empty($status)) ? '' : 'gdb-transaction-status-pending';

        ob_start();
        ?>
        <div class="gdb-transaction-item <?php echo esc_attr($tx_class . ' ' . $pending_class); ?>">
            <?php if ($settings['show_amount']) : ?>
                <div class="gdb-transaction-amount <?php echo esc_attr($amount_class); ?>">
                    <span class="gdb-amount-sign"><?php echo esc_html($sign); ?></span>
                    <?php echo wp_kses_post(gdb_price(abs($tx->amount))); ?>
                </div>
            <?php endif; ?>

            <div class="gdb-transaction-info">
                <div class="gdb-transaction-type">
                    <span><?php echo esc_html($label); ?></span>
                    <?php if ($settings['show_date']) : ?>
                        <span class="gdb-transaction-date-inline"><?php echo esc_html(gdb_date_jalali($tx->created_at, true)); ?></span>
                    <?php endif; ?>
                    <?php echo wp_kses_post($status_display); ?>
                </div>

                <?php if ($settings['show_description'] && !empty($tx->description)) : ?>
                    <div class="gdb-transaction-description"><?php echo esc_html($tx->description); ?></div>
                <?php endif; ?>

                <?php echo wp_kses_post($fee_display); ?>
                <?php echo wp_kses_post($admin_info); ?>

                <?php if ($settings['show_balance_after']) : ?>
                    <div class="gdb-transaction-balance">
                        <?php esc_html_e('موجودی بعد:', 'golden-dashboard'); ?>
                        <?php echo wp_kses_post(gdb_price($tx->balance_after)); ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($settings['show_icon']) : ?>
                <div class="gdb-transaction-icon"><span><?php echo esc_html($sign); ?></span></div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

}

if (!function_exists('gdb_render_history_table_paginated')) {

    function gdb_render_history_table_paginated($transactions, $columns, $total, $pages, $current_page, $per_page, $filter_type, $filter_transaction_type, $show_pagination = true)
    {
        $columns = wp_parse_args($columns, [
            'row_number'     => true,
            'date'           => true,
            'type'           => true,
            'amount'         => true,
            'balance_before' => true,
            'balance_after'  => true,
            'status'         => true,
            'fee'            => true,
            'description'    => true,
        ]);

        if (!$transactions) {
            echo wp_kses_post(gdb_empty(__('هیچ تراکنشی با این فیلترها یافت نشد.', 'golden-dashboard')));
            return;
        }

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
        <div class="gdb-history-table-wrap">
            <table class="gdb-history-table">
                <thead>
                    <tr>
                        <?php if ($columns['row_number']) : ?>
                            <th><?php esc_html_e('ردیف', 'golden-dashboard'); ?></th>
                        <?php endif; ?>
                        <?php if ($columns['date']) : ?>
                            <th><?php esc_html_e('تاریخ', 'golden-dashboard'); ?></th>
                        <?php endif; ?>
                        <?php if ($columns['type']) : ?>
                            <th><?php esc_html_e('نوع تراکنش', 'golden-dashboard'); ?></th>
                        <?php endif; ?>
                        <?php if ($columns['amount']) : ?>
                            <th><?php esc_html_e('مبلغ', 'golden-dashboard'); ?></th>
                        <?php endif; ?>
                        <?php if ($columns['fee']) : ?>
                            <th><?php esc_html_e('کارمزد', 'golden-dashboard'); ?></th>
                        <?php endif; ?>
                        <?php if ($columns['balance_before']) : ?>
                            <th><?php esc_html_e('موجودی قبل', 'golden-dashboard'); ?></th>
                        <?php endif; ?>
                        <?php if ($columns['balance_after']) : ?>
                            <th><?php esc_html_e('موجودی بعد', 'golden-dashboard'); ?></th>
                        <?php endif; ?>
                        <?php if ($columns['status']) : ?>
                            <th><?php esc_html_e('وضعیت', 'golden-dashboard'); ?></th>
                        <?php endif; ?>
                        <?php if ($columns['description']) : ?>
                            <th><?php esc_html_e('توضیحات', 'golden-dashboard'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $row_num = (($current_page - 1) * $per_page) + 1; ?>
                    <?php foreach ($transactions as $tx) :
                        $is_credit = ($tx->type === 'credit');
                        $badge_class = $is_credit ? 'gdb-badge-credit' : 'gdb-badge-debit';
                        $badge_text = gdb_transaction_label($tx, $is_credit);
                        $sign = $is_credit ? '+' : '−';
                        $amount_class = $is_credit ? 'gdb-amount-credit' : 'gdb-amount-debit';
                        $balance_before = isset($tx->balance_before) ? $tx->balance_before : 0;
                        $fee_display = (isset($tx->fee_amount) && $tx->fee_amount > 0) ? gdb_price($tx->fee_amount) : '-';

                        $status = isset($tx->status) ? $tx->status : 'completed';
                        $status_label = isset($status_labels[$status]) ? $status_labels[$status] : $status;
                        $status_class = isset($status_classes[$status]) ? $status_classes[$status] : '';
                        $status_badge = '<span class="gdb-history-badge ' . esc_attr($status_class) . '">' . esc_html($status_label) . '</span>';

                        $row_class = '';
                        if (!in_array($status, ['completed', 'refunded'])) {
                            $row_class = 'gdb-row-pending';
                        }

                        $admin_info = '';
                        $show_admin_info = in_array($tx->transaction_type, ['withdraw_request', 'order_payment']) 
                                           && in_array($status, ['completed', 'rejected', 'cancelled', 'refunded', 'failed']);
                        if ($show_admin_info) {
                            $info_parts = [];
                            if (!empty($tx->admin_note)) {
                                $info_parts[] = '<span class="gdb-admin-note">' . esc_html($tx->admin_note) . '</span>';
                            }
                            if (!empty($tx->bank_transaction_id)) {
                                /* translators: %s: bank transaction ID */
                                $info_parts[] = '<span class="gdb-bank-id">' . sprintf(__('شماره تراکنش: %s', 'golden-dashboard'), esc_html($tx->bank_transaction_id)) . '</span>';
                            }
                            if (!empty($tx->bank_date)) {
                                $bank_date_display = gdb_date_jalali($tx->bank_date, false);
                                if ($bank_date_display) {
                                    /* translators: %s: bank deposit date */
                                    $info_parts[] = '<span class="gdb-bank-date">' . sprintf(__('تاریخ واریز: %s', 'golden-dashboard'), $bank_date_display) . '</span>';
                                }
                            }
                            if (!empty($info_parts)) {
                                $admin_info = '<div class="gdb-admin-info">' . implode(' | ', $info_parts) . '</div>';
                            }
                        }
                    ?>
                        <tr class="<?php echo esc_attr($row_class); ?>">
                            <?php if ($columns['row_number']) : ?>
                                <td data-title="<?php esc_attr_e('ردیف', 'golden-dashboard'); ?>"><?php echo esc_html($row_num++); ?></td>
                            <?php endif; ?>
                            <?php if ($columns['date']) : ?>
                                <td data-title="<?php esc_attr_e('تاریخ', 'golden-dashboard'); ?>">
                                    <?php echo esc_html(gdb_date_jalali($tx->created_at, true)); ?>
                                </td>
                            <?php endif; ?>
                            <?php if ($columns['type']) : ?>
                                <td data-title="<?php esc_attr_e('نوع تراکنش', 'golden-dashboard'); ?>">
                                    <span class="gdb-history-badge <?php echo esc_attr($badge_class); ?>">
                                        <?php echo esc_html($badge_text); ?>
                                    </span>
                                </td>
                            <?php endif; ?>
                            <?php if ($columns['amount']) : ?>
                                <td data-title="<?php esc_attr_e('مبلغ', 'golden-dashboard'); ?>">
                                    <span class="gdb-history-amount <?php echo esc_attr($amount_class); ?>">
                                        <?php echo esc_html($sign); ?>
                                        <?php echo wp_kses_post(gdb_price(abs($tx->amount))); ?>
                                    </span>
                                </td>
                            <?php endif; ?>
                            <?php if ($columns['fee']) : ?>
                                <td data-title="<?php esc_attr_e('کارمزد', 'golden-dashboard'); ?>">
                                    <?php echo wp_kses_post($fee_display); ?>
                                </td>
                            <?php endif; ?>
                            <?php if ($columns['balance_before']) : ?>
                                <td data-title="<?php esc_attr_e('موجودی قبل', 'golden-dashboard'); ?>">
                                    <?php echo wp_kses_post(gdb_price($balance_before)); ?>
                                </td>
                            <?php endif; ?>
                            <?php if ($columns['balance_after']) : ?>
                                <td data-title="<?php esc_attr_e('موجودی بعد', 'golden-dashboard'); ?>">
                                    <strong><?php echo wp_kses_post(gdb_price($tx->balance_after)); ?></strong>
                                </td>
                            <?php endif; ?>
                            <?php if ($columns['status']) : ?>
                                <td data-title="<?php esc_attr_e('وضعیت', 'golden-dashboard'); ?>">
                                    <?php echo wp_kses_post($status_badge); ?>
                                </td>
                            <?php endif; ?>
                            <?php if ($columns['description']) : ?>
                                <td data-title="<?php esc_attr_e('توضیحات', 'golden-dashboard'); ?>">
                                    <?php echo esc_html($tx->description); ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($show_pagination && $pages > 1) : ?>
            <div class="gdb-history-pagination" style="margin-top:16px; text-align:center;">
                <?php
                $base_url = remove_query_arg(['history_page', 'filter_type', 'filter_transaction_type']);
                $base_url = add_query_arg([
                    'filter_type' => $filter_type,
                    'filter_transaction_type' => $filter_transaction_type,
                ], $base_url);

                echo wp_kses_post(paginate_links([
                    'base'      => add_query_arg('history_page', '%#%', $base_url),
                    'format'    => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total'     => $pages,
                    'current'   => $current_page,
                ]));
                ?>
            </div>
        <?php endif; ?>

        <style>
            .gdb-history-pagination .page-numbers {
                display: inline-block;
                padding: 6px 12px;
                margin: 0 2px;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                background: #fff;
                color: #374151;
                font-size: 14px;
                text-decoration: none;
                transition: 0.2s;
            }
            .gdb-history-pagination .page-numbers.current {
                background: #2563eb;
                color: #fff;
                border-color: #2563eb;
            }
            .gdb-history-pagination .page-numbers:hover {
                background: #f3f4f6;
            }
            .gdb-history-pagination .page-numbers.current:hover {
                background: #2563eb;
            }
            .gdb-history-pagination {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 4px;
            }
        </style>
        <?php
    }

}

if (!function_exists('gdb_get_wallet_history')) {

    function gdb_get_wallet_history($user_id, $filter_type = '', $filter_transaction_type = '')
    {
        $result = gdb_get_wallet_history_paginated($user_id, $filter_type, $filter_transaction_type, 1, 999999);
        return $result['items'];
    }

}

if (!function_exists('gdb_get_wallet_history_paginated')) {

    function gdb_get_wallet_history_paginated($user_id, $filter_type = '', $filter_transaction_type = '', $page = 1, $per_page = 20)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gd_wallet_transactions';

        $where = ' WHERE user_id = %d ';
        $params = [$user_id];

        if ($filter_type === 'credit') {
            $where .= " AND type = 'credit' ";
        } elseif ($filter_type === 'debit') {
            $where .= " AND type = 'debit' ";
        }

        if (!empty($filter_transaction_type)) {
            $where .= " AND transaction_type = %s ";
            $params[] = $filter_transaction_type;
        }

        // phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $where is built dynamically from a fixed set of %d/%s placeholders always pushed to $params in the same order and count; manually verified to match at every branch.
        $count_sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} t1 {$where}",
            $params
        );
        // phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
        $total = (int) $wpdb->get_var($count_sql);

        if ($total === 0) {
            return ['items' => [], 'total' => 0, 'pages' => 0];
        }

        $page = max(1, intval($page));
        $per_page = max(1, intval($per_page));
        $offset = ($page - 1) * $per_page;

        // phpcs:disable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- $where contributes 1 or 2 placeholders and $params has the matching count; combined with the 2 literal LIMIT/OFFSET placeholders and array_merge($params, [$per_page, $offset]), counts always match. Manually verified.
        $sql = $wpdb->prepare(
            "SELECT t1.*,
                (SELECT balance_after
                 FROM {$table} t2
                 WHERE t2.user_id = t1.user_id
                   AND t2.id < t1.id
                 ORDER BY t2.id DESC
                 LIMIT 1) AS balance_before
             FROM {$table} t1
             {$where}
             ORDER BY t1.id DESC
             LIMIT %d OFFSET %d",
            array_merge($params, [$per_page, $offset])
        );
        // phpcs:enable WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

        $items = $wpdb->get_results($sql);
        $pages = ceil($total / $per_page);

        return [
            'items' => $items,
            'total' => $total,
            'pages' => $pages,
        ];
    }
}

if (!function_exists('gdb_generate_tracking_code')) {
    function gdb_generate_tracking_code() {
        return (string) wp_rand(100000000000, 999999999999);
    }
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange

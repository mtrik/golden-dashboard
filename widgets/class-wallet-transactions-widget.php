<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class GDB_Wallet_Transactions_Widget extends GDB_Base_Widget
{
    public function get_name()
    {
        return 'gdb-wallet-transactions';
    }

    public function get_title()
    {
        return __('تراکنش‌های کیف پول', 'golden-dashboard');
    }

    public function get_icon()
    {
        return 'eicon-post-list';
    }

    public function get_keywords()
    {
        return ['wallet', 'transaction', 'history', 'gold'];
    }

    protected function register_controls()
    {

        $this->start_controls_section(
            'section_content',
            [
                'label' => __('تنظیمات', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'per_page',
            [
                'label'   => __('تعداد تراکنش در هر صفحه', 'golden-dashboard'),
                'type'    => Controls_Manager::SELECT,
                'default' => 10,
                'options' => [
                    5   => __('۵', 'golden-dashboard'),
                    10  => __('۱۰', 'golden-dashboard'),
                    20  => __('۲۰', 'golden-dashboard'),
                    30  => __('۳۰', 'golden-dashboard'),
                    50  => __('۵۰', 'golden-dashboard'),
                    100 => __('۱۰۰', 'golden-dashboard'),
                ],
                'description' => __('تعداد رکوردهایی که در هر صفحه نمایش داده می‌شوند.', 'golden-dashboard'),
            ]
        );

        
        $this->add_control(
            'show_history_row_number',
            [
                'label'   => __('نمایش شماره ردیف', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_history_date',
            [
                'label'   => __('نمایش ستون تاریخ', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_history_type',
            [
                'label'   => __('نمایش ستون نوع تراکنش', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_history_amount',
            [
                'label'   => __('نمایش ستون مبلغ', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_history_balance_before',
            [
                'label'   => __('نمایش ستون موجودی قبل', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_history_balance_after',
            [
                'label'   => __('نمایش ستون موجودی بعد', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_history_status',
            [
                'label'   => __('نمایش ستون وضعیت', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_history_description',
            [
                'label'   => __('نمایش ستون توضیحات', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'filter_transaction_types',
            [
                'label'       => __('دسته‌های قابل نمایش در فیلتر', 'golden-dashboard'),
                'type'        => Controls_Manager::SELECT2,
                'multiple'    => true,
                'options'     => [
                    'admin_credit'        => __('شارژ توسط مدیر', 'golden-dashboard'),
                    'admin_debit'         => __('برداشت توسط مدیر', 'golden-dashboard'),
                    'order_payment'       => __('پرداخت سفارش', 'golden-dashboard'),
                    'order_refund'        => __('عودت وجه', 'golden-dashboard'),
                    'withdraw'            => __('برداشت', 'golden-dashboard'),
                    'withdraw_request'    => __('درخواست برداشت', 'golden-dashboard'),
                    
                    
                    
                    
                    'gold_purchase'       => __('خرید طلا/فلز', 'golden-dashboard'),
                    'cashback'            => __('کش‌بک', 'golden-dashboard'),
                    'cashback_reversal'   => __('برگشت کش‌بک', 'golden-dashboard'),
                ],
                'default'     => ['admin_credit', 'admin_debit', 'order_payment', 'order_refund', 'withdraw', 'withdraw_request', 'gold_purchase', 'cashback', 'cashback_reversal'],
                'description' => __('دسته‌هایی که در منوی فیلتر نمایش داده می‌شوند.', 'golden-dashboard'),
            ]
        );

        $this->end_controls_section();

        
        $this->start_controls_section(
            'section_style_container',
            [
                'label' => __('کانتینر', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label'      => __('فاصله داخلی', 'golden-dashboard'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-transactions-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        
        $this->start_controls_section(
            'section_style_table',
            [
                'label' => __('جدول', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'table_header_bg',
            [
                'label'     => __('رنگ پس‌زمینه هدر', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f8fafc',
                'selectors' => [
                    '{{WRAPPER}} .gdb-history-table thead th' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'table_header_color',
            [
                'label'     => __('رنگ متن هدر', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#374151',
                'selectors' => [
                    '{{WRAPPER}} .gdb-history-table thead th' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'table_header_typography',
                'selector' => '{{WRAPPER}} .gdb-history-table thead th',
            ]
        );

        $this->add_control(
            'table_cell_color',
            [
                'label'     => __('رنگ متن سلول‌ها', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#111827',
                'selectors' => [
                    '{{WRAPPER}} .gdb-history-table tbody td' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'table_cell_typography',
                'selector' => '{{WRAPPER}} .gdb-history-table tbody td',
            ]
        );

        $this->add_control(
            'table_row_hover_bg',
            [
                'label'     => __('رنگ پس‌زمینه ردیف هاور', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f8fafc',
                'selectors' => [
                    '{{WRAPPER}} .gdb-history-table tbody tr:hover' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'table_border_color',
            [
                'label'     => __('رنگ خطوط جدول', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#e5e7eb',
                'selectors' => [
                    '{{WRAPPER}} .gdb-history-table thead th' => 'border-bottom-color: {{VALUE}};',
                    '{{WRAPPER}} .gdb-history-table tbody td' => 'border-bottom-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'table_pending_row_bg',
            [
                'label'     => __('رنگ پس‌زمینه ردیف در انتظار', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#fffbeb',
                'selectors' => [
                    '{{WRAPPER}} .gdb-history-table tbody tr.gdb-row-pending' => 'background: {{VALUE}};',
                ],
                'description' => __('رنگ ردیف‌هایی که وضعیت آنها تکمیل شده یا بازگشت وجه نیست.', 'golden-dashboard'),
            ]
        );

        $this->end_controls_section();

        
        $this->start_controls_section(
            'section_style_filters',
            [
                'label' => __('فیلترها و دکمه‌ها', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'filters_bg',
            [
                'label'     => __('رنگ پس‌زمینه', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f8fafc',
                'selectors' => [
                    '{{WRAPPER}} .gdb-history-filters' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'filters_padding',
            [
                'label'      => __('فاصله داخلی', 'golden-dashboard'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'    => 16,
                    'right'  => 16,
                    'bottom' => 16,
                    'left'   => 16,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-history-filters' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'filter_label_color',
            [
                'label'     => __('رنگ برچسب‌ها', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#374151',
                'selectors' => [
                    '{{WRAPPER}} .gdb-filter-group label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_select_bg',
            [
                'label'     => __('رنگ پس‌زمینه سلکت‌ها', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .gdb-filter-group select' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_select_color',
            [
                'label'     => __('رنگ متن سلکت‌ها', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#111827',
                'selectors' => [
                    '{{WRAPPER}} .gdb-filter-group select' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'filter_select_border',
                'selector' => '{{WRAPPER}} .gdb-filter-group select',
            ]
        );

        
        $this->add_control(
            'filter_button_heading',
            [
                'label'     => __('دکمه فیلتر', 'golden-dashboard'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'filter_button_bg',
            [
                'label'     => __('رنگ پس‌زمینه', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#2563eb',
                'selectors' => [
                    '{{WRAPPER}} .gdb-filter-submit' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_button_color',
            [
                'label'     => __('رنگ متن', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .gdb-filter-submit' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_button_hover_bg',
            [
                'label'     => __('رنگ پس‌زمینه هاور', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#1d4ed8',
                'selectors' => [
                    '{{WRAPPER}} .gdb-filter-submit:hover' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'filter_button_border',
                'selector' => '{{WRAPPER}} .gdb-filter-submit',
            ]
        );

        $this->add_responsive_control(
            'filter_button_radius',
            [
                'label'      => __('گردی گوشه', 'golden-dashboard'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default'    => [
                    'top'    => 6,
                    'right'  => 6,
                    'bottom' => 6,
                    'left'   => 6,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-filter-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        
        $this->add_control(
            'export_buttons_heading',
            [
                'label'     => __('دکمه‌های خروجی', 'golden-dashboard'),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'export_button_bg',
            [
                'label'     => __('رنگ پس‌زمینه', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#6b7280',
                'selectors' => [
                    '{{WRAPPER}} .gdb-export-btn' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'export_button_color',
            [
                'label'     => __('رنگ متن/آیکون', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .gdb-export-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'export_button_hover_bg',
            [
                'label'     => __('رنگ پس‌زمینه هاور', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#4b5563',
                'selectors' => [
                    '{{WRAPPER}} .gdb-export-btn:hover' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'export_button_border',
                'selector' => '{{WRAPPER}} .gdb-export-btn',
            ]
        );

        $this->add_responsive_control(
            'export_button_radius',
            [
                'label'      => __('گردی گوشه', 'golden-dashboard'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default'    => [
                    'top'    => 6,
                    'right'  => 6,
                    'bottom' => 6,
                    'left'   => 6,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-export-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'export_button_padding',
            [
                'label'      => __('فاصله داخلی', 'golden-dashboard'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'    => 8,
                    'right'  => 12,
                    'bottom' => 8,
                    'left'   => 12,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-export-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        if (!is_user_logged_in()) {
            echo wp_kses_post(gdb_login_required());
            return;
        }

        $settings = $this->get_settings_for_display();
        $user_id  = get_current_user_id();
        $per_page = absint($settings['per_page']) ?: 10;

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filtering/pagination of the current user's own transaction list; no data is written or changed here.
        $filter_type = isset($_GET['filter_type']) ? sanitize_text_field(wp_unslash($_GET['filter_type'])) : '';
        $filter_transaction_type = isset($_GET['filter_transaction_type']) ? sanitize_text_field(wp_unslash($_GET['filter_transaction_type'])) : '';
        $current_page = isset($_GET['history_page']) ? absint(wp_unslash($_GET['history_page'])) : 1;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if ($current_page < 1) $current_page = 1;

        $result = gdb_get_wallet_history_paginated($user_id, $filter_type, $filter_transaction_type, $current_page, $per_page);
        $transactions = $result['items'];
        $total = $result['total'];
        $pages = $result['pages'];

        $columns = [
            'row_number'     => ($settings['show_history_row_number'] === 'yes'),
            'date'           => ($settings['show_history_date'] === 'yes'),
            'type'           => ($settings['show_history_type'] === 'yes'),
            'amount'         => ($settings['show_history_amount'] === 'yes'),
            'balance_before' => ($settings['show_history_balance_before'] === 'yes'),
            'balance_after'  => ($settings['show_history_balance_after'] === 'yes'),
            'status'         => ($settings['show_history_status'] === 'yes'),
            'fee'            => true,
            'description'    => ($settings['show_history_description'] === 'yes'),
        ];

        $widget_id = $this->get_id();

        ?>
        <div class="gdb-transactions-wrapper">

            <form method="get" class="gdb-history-filters" data-ajax-filter="1">
                <?php
                // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only re-display of existing GET query params as hidden fields to preserve filter state; all values are escaped on output and no data is written or changed here.
                $current_params = wp_unslash($_GET);
                unset($current_params['filter_type']);
                unset($current_params['filter_transaction_type']);
                unset($current_params['history_page']);
                foreach ($current_params as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            echo '<input type="hidden" name="' . esc_attr($key) . '[]" value="' . esc_attr($v) . '">';
                        }
                    } else {
                        echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                    }
                }
                // phpcs:enable WordPress.Security.NonceVerification.Recommended
                ?>

                <input type="hidden" name="show_history_row_number" value="<?php echo esc_attr($settings['show_history_row_number'] === 'yes' ? '1' : ''); ?>">
                <input type="hidden" name="show_history_date" value="<?php echo esc_attr($settings['show_history_date'] === 'yes' ? '1' : ''); ?>">
                <input type="hidden" name="show_history_type" value="<?php echo esc_attr($settings['show_history_type'] === 'yes' ? '1' : ''); ?>">
                <input type="hidden" name="show_history_amount" value="<?php echo esc_attr($settings['show_history_amount'] === 'yes' ? '1' : ''); ?>">
                <input type="hidden" name="show_history_balance_before" value="<?php echo esc_attr($settings['show_history_balance_before'] === 'yes' ? '1' : ''); ?>">
                <input type="hidden" name="show_history_balance_after" value="<?php echo esc_attr($settings['show_history_balance_after'] === 'yes' ? '1' : ''); ?>">
                <input type="hidden" name="show_history_status" value="<?php echo esc_attr($settings['show_history_status'] === 'yes' ? '1' : ''); ?>">
                <input type="hidden" name="show_history_description" value="<?php echo esc_attr($settings['show_history_description'] === 'yes' ? '1' : ''); ?>">
                <input type="hidden" name="history_page" value="<?php echo esc_attr($current_page); ?>">
                <input type="hidden" name="history_per_page" value="<?php echo esc_attr($per_page); ?>">

                <div class="gdb-filter-group">
                    <label for="filter_type_<?php echo esc_attr($widget_id); ?>"><?php esc_html_e('نوع:', 'golden-dashboard'); ?></label>
                    <select name="filter_type" id="filter_type_<?php echo esc_attr($widget_id); ?>">
                        <option value=""><?php esc_html_e('همه', 'golden-dashboard'); ?></option>
                        <option value="credit" <?php selected($filter_type, 'credit'); ?>><?php esc_html_e('شارژ', 'golden-dashboard'); ?></option>
                        <option value="debit" <?php selected($filter_type, 'debit'); ?>><?php esc_html_e('برداشت', 'golden-dashboard'); ?></option>
                    </select>
                </div>

                <div class="gdb-filter-group">
                    <label for="filter_transaction_type_<?php echo esc_attr($widget_id); ?>"><?php esc_html_e('دسته:', 'golden-dashboard'); ?></label>
                    <select name="filter_transaction_type" id="filter_transaction_type_<?php echo esc_attr($widget_id); ?>">
                        <option value=""><?php esc_html_e('همه', 'golden-dashboard'); ?></option>
                        <?php
                        $all_types = [
                            'admin_credit'        => __('شارژ توسط مدیر', 'golden-dashboard'),
                            'admin_debit'         => __('برداشت توسط مدیر', 'golden-dashboard'),
                            'order_payment'       => __('پرداخت سفارش', 'golden-dashboard'),
                            'order_refund'        => __('عودت وجه', 'golden-dashboard'),
                            'withdraw'            => __('برداشت', 'golden-dashboard'),
                            'withdraw_request'    => __('درخواست برداشت', 'golden-dashboard'),
                            'gold_purchase'       => __('خرید طلا/فلز', 'golden-dashboard'),
                            'cashback'            => __('کش‌بک', 'golden-dashboard'),
                            'cashback_reversal'   => __('برگشت کش‌بک', 'golden-dashboard'),
                        ];
                        $allowed_filter_types = $settings['filter_transaction_types'];
                        if (!is_array($allowed_filter_types)) {
                            $allowed_filter_types = [];
                        }
                        foreach ($all_types as $value => $label) :
                            if (!in_array($value, $allowed_filter_types)) continue;
                            $selected = ($filter_transaction_type === $value) ? 'selected' : '';
                        ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php echo esc_attr($selected); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="gdb-filter-submit"><?php esc_html_e('فیلتر', 'golden-dashboard'); ?></button>

                <div class="gdb-export-buttons">
                    <button type="button" class="gdb-export-btn gdb-export-csv" title="<?php esc_attr_e('خروجی CSV', 'golden-dashboard'); ?>">
                        <span class="dashicons dashicons-download"></span>
                        <span class="gdb-btn-label"><?php esc_html_e('CSV', 'golden-dashboard'); ?></span>
                    </button>
                    <button type="button" class="gdb-export-btn gdb-export-print" title="<?php esc_attr_e('چاپ', 'golden-dashboard'); ?>">
                        <span class="dashicons dashicons-printer"></span>
                        <span class="gdb-btn-label"><?php esc_html_e('چاپ', 'golden-dashboard'); ?></span>
                    </button>
                </div>
            </form>

            <div class="gdb-history-results">
                <?php
                if ($transactions) {
                    gdb_render_history_table_paginated(
                        $transactions,
                        $columns,
                        $total,
                        $pages,
                        $current_page,
                        $per_page,
                        $filter_type,
                        $filter_transaction_type,
                        true 
                    );
                } else {
                    echo wp_kses_post(gdb_empty(__('هیچ تراکنشی با این فیلترها یافت نشد.', 'golden-dashboard')));
                }
                ?>
            </div>

        </div>

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
                font-weight: 600;
            }
            .gdb-amount-sign {
                display: inline-block !important;
                margin-left: 1px !important;
                margin-right: 1px !important;
            }
            .gdb-history-table td .gdb-history-amount,
            .gdb-history-table td .gdb-amount-sign {
                vertical-align: middle !important;
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

            .gdb-export-buttons {
                display: inline-flex;
                gap: 6px;
                margin-right: 10px;
                flex-wrap: wrap;
            }
            .gdb-export-btn {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                background: #6b7280;
                color: #fff;
                border: none;
                padding: 6px 12px;
                border-radius: 6px;
                font-size: 13px;
                cursor: pointer;
                transition: background 0.2s, transform 0.1s;
                line-height: 1.5;
                text-decoration: none;
            }
            .gdb-export-btn:hover {
                background: #4b5563;
                transform: translateY(-1px);
            }
            .gdb-export-btn .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }
            .gdb-export-btn .gdb-btn-label {
                display: inline;
            }
            @media (max-width: 640px) {
                .gdb-export-btn .gdb-btn-label {
                    display: none;
                }
                .gdb-export-buttons {
                    margin-right: 4px;
                }
                .gdb-export-btn {
                    padding: 6px 8px;
                }
            }

            @media (max-width: 640px) {
                .gdb-history-filters {
                    flex-direction: column;
                    align-items: stretch;
                    gap: 10px;
                }
                .gdb-filter-group {
                    flex-wrap: wrap;
                }
                .gdb-filter-group select {
                    width: 100%;
                }
                .gdb-filter-submit {
                    width: 100%;
                }
                .gdb-export-buttons {
                    justify-content: center;
                    margin-right: 0;
                }
            }

            @media (max-width: 782px) {
                .gdb-history-table {
                    display: block;
                    overflow-x: auto;
                    white-space: nowrap;
                }
                .gdb-history-table th,
                .gdb-history-table td {
                    padding: 6px 8px !important;
                    font-size: 13px;
                }
                .gdb-history-badge {
                    font-size: 11px !important;
                    padding: 3px 8px !important;
                }
                .gdb-history-amount {
                    font-size: 13px !important;
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
                    padding: 2px 6px !important;
                }
                .gdb-history-amount {
                    font-size: 12px !important;
                }
            }
        </style>

        <script>
            (function($) {
                'use strict';
                $(document).on('click', '.gdb-export-csv', function(e) {
                    e.preventDefault();
                    var $form = $(this).closest('form.gdb-history-filters');
                    var data = $form.serializeArray();
                    data.push({ name: 'action', value: 'gdb_export_transactions_csv' });
                    data.push({ name: 'nonce', value: gdb.nonce });

                    var form = $('<form method="POST" action="' + gdb.ajaxurl + '"></form>');
                    $.each(data, function(i, field) {
                        $('<input type="hidden">')
                            .attr('name', field.name)
                            .attr('value', field.value)
                            .appendTo(form);
                    });
                    $('body').append(form);
                    form.submit();
                    form.remove();
                });

                $(document).on('click', '.gdb-export-print', function(e) {
                    e.preventDefault();

                    var $wrapper = $(this).closest('.gdb-transactions-wrapper');
                    var $table = $wrapper.find('.gdb-history-results table.gdb-history-table').first();

                    if (!$table.length) {
                        window.alert(<?php echo wp_json_encode(__('داده‌ای برای چاپ وجود ندارد.', 'golden-dashboard')); ?>);
                        return;
                    }

                    var printWindow = window.open('', '_blank', 'width=900,height=700');
                    if (!printWindow) {
                        window.alert(<?php echo wp_json_encode(__('اجازه بازکردن پنجره چاپ داده نشد. لطفاً مسدودکننده پاپ‌آپ مرورگر را غیرفعال کنید.', 'golden-dashboard')); ?>);
                        return;
                    }

                    var siteTitle   = <?php echo wp_json_encode(get_bloginfo('name')); ?>;
                    var printTitle  = <?php echo wp_json_encode(__('گزارش تراکنش‌ها', 'golden-dashboard')); ?>;
                    var now         = new Date();
                    var printedAt;
                    try {
                        printedAt = now.toLocaleDateString('fa-IR') + ' - ' + now.toLocaleTimeString('fa-IR');
                    } catch (err) {
                        printedAt = now.toLocaleString();
                    }

                    var docHtml = ''
                        + '<!DOCTYPE html><html dir="rtl" lang="fa"><head><meta charset="utf-8">'
                        + '<title>' + printTitle + ' - ' + siteTitle + '</title>'
                        + '<style>'
                        +   '@page { size: A4; margin: 15mm; }'
                        +   '* { box-sizing: border-box; }'
                        +   'body { font-family: Tahoma, "Segoe UI", Arial, sans-serif; direction: rtl; color:#1f2937; margin:0; padding:0; }'
                        +   '.gdb-print-header { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:16px; border-bottom:2px solid #e5e7eb; padding-bottom:10px; }'
                        +   '.gdb-print-header h1 { font-size:16px; margin:0; }'
                        +   '.gdb-print-header span { font-size:11px; color:#6b7280; }'
                        +   'table { width:100%; border-collapse:collapse; font-size:12px; }'
                        +   'th, td { border:1px solid #e5e7eb; padding:6px 8px; text-align:right; }'
                        +   'thead th { background:#f3f4f6; }'
                        +   '@media print { a { color:inherit; text-decoration:none; } }'
                        + '</style>'
                        + '</head><body>'
                        + '<div class="gdb-print-header"><h1>' + printTitle + '</h1><span>' + printedAt + '</span></div>'
                        + $table.prop('outerHTML')
                        + '<script>window.onload = function(){ window.print(); setTimeout(function(){ window.close(); }, 200); };<\/script>'
                        + '</body></html>';

                    printWindow.document.open();
                    printWindow.document.write(docHtml);
                    printWindow.document.close();
                });
            })(jQuery);
        </script>
        <?php
    }
}
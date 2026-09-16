<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

class GDB_Wallet_Withdraw_Widget extends GDB_Base_Widget {

    public function get_name() {
        return 'gdb-wallet-withdraw';
    }

    public function get_title() {
        return __('برداشت از کیف پول', 'golden-dashboard');
    }

    public function get_icon() {
        return 'eicon-money';
    }

    public function get_keywords() {
        return ['wallet', 'withdraw', 'cashout', 'gold'];
    }

    public function get_script_depends() {
        return ['gdb-withdraw'];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'content_section',
            [
                'label' => __('تنظیمات پوسته', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'title',
            [
                'label'   => __('عنوان', 'golden-dashboard'),
                'type'    => Controls_Manager::TEXT,
                'default' => __('برداشت از کیف پول', 'golden-dashboard'),
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label'   => __('نمایش عنوان', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_icon',
            [
                'label'   => __('نمایش آیکون', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'icon',
            [
                'label'     => __('آیکون', 'golden-dashboard'),
                'type'      => Controls_Manager::ICONS,
                'default'   => [
                    'value'   => 'fas fa-hand-holding-usd',
                    'library' => 'fa-solid',
                ],
                'condition' => ['show_icon' => 'yes'],
            ]
        );

        $this->add_control(
            'show_balance',
            [
                'label'   => __('نمایش موجودی فعلی', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'balance_label',
            [
                'label'     => __('برچسب موجودی', 'golden-dashboard'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('موجودی قابل برداشت:', 'golden-dashboard'),
                'condition' => ['show_balance' => 'yes'],
            ]
        );


        $min_amount_default = intval(get_option('gdb_withdraw_min_amount', 10000));
        $max_amount_default = intval(get_option('gdb_withdraw_max_amount', 50000000));

        $this->add_control(
            'min_amount',
            [
                'label'       => __('حداقل مبلغ برداشت', 'golden-dashboard'),
                'type'        => Controls_Manager::NUMBER,
                'default'     => $min_amount_default,
                'min'         => 1000,
                'step'        => 1000,
            ]
        );

        $this->add_control(
            'max_amount',
            [
                'label'       => __('حداکثر مبلغ برداشت', 'golden-dashboard'),
                'type'        => Controls_Manager::NUMBER,
                'default'     => $max_amount_default,
                'min'         => 10000,
                'step'        => 10000,
            ]
        );

        $this->add_control(
            'step_amount',
            [
                'label'       => __('گام افزایش', 'golden-dashboard'),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 1000,
                'min'         => 1000,
                'step'        => 1000,
            ]
        );

        $this->add_control(
            'placeholder_text',
            [
                'label'   => __('متن راهنما', 'golden-dashboard'),
                'type'    => Controls_Manager::TEXT,
                'default' => __('مبلغ مورد نظر برای برداشت را وارد کنید', 'golden-dashboard'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label'   => __('متن دکمه برداشت', 'golden-dashboard'),
                'type'    => Controls_Manager::TEXT,
                'default' => __('درخواست برداشت', 'golden-dashboard'),
            ]
        );

        $this->add_control(
            'min_max_text',
            [
                'label'   => __('متن حداقل و حداکثر', 'golden-dashboard'),
                'type'    => Controls_Manager::TEXT,
                'default' => __('حداقل: {min} - حداکثر: {max}', 'golden-dashboard'),
            ]
        );

        $this->add_control(
            'show_fee_info',
            [
                'label'   => __('نمایش اطلاعات کارمزد', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'description' => __('در صورت فعال بودن و تنظیم کارمزد در بخش تنظیمات، مبلغ کارمزد و مبلغ قابل واریز به کاربر نشان داده می‌شود.', 'golden-dashboard'),
            ]
        );

        $this->end_controls_section();



        $this->start_controls_section(
            'section_style_card',
            [
                'label' => __('کارت', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_bg',
            [
                'label'     => __('رنگ پس‌زمینه', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .gdb-card' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .gdb-card',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .gdb-card',
            ]
        );

        $this->add_responsive_control(
            'card_radius',
            [
                'label'      => __('گردی گوشه‌ها', 'golden-dashboard'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default'    => [
                    'top'    => 16,
                    'right'  => 16,
                    'bottom' => 16,
                    'left'   => 16,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'card_padding',
            [
                'label'      => __('فاصله داخلی', 'golden-dashboard'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'    => 24,
                    'right'  => 24,
                    'bottom' => 24,
                    'left'   => 24,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();



        $this->start_controls_section(
            'section_style_icon',
            [
                'label'     => __('آیکون', 'golden-dashboard'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => ['show_icon' => 'yes'],
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label'     => __('رنگ آیکون', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#2563eb',
                'selectors' => [
                    '{{WRAPPER}} .gdb-withdraw-icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'icon_bg',
            [
                'label'     => __('رنگ پس‌زمینه آیکون', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#eff6ff',
                'selectors' => [
                    '{{WRAPPER}} .gdb-withdraw-icon' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label'      => __('اندازه آیکون', 'golden-dashboard'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 16,
                        'max' => 64,
                    ],
                ],
                'default'    => ['size' => 32],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-withdraw-icon i'   => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .gdb-withdraw-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_box_size',
            [
                'label'      => __('اندازه باکس آیکون', 'golden-dashboard'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 40,
                        'max' => 120,
                    ],
                ],
                'default'    => ['size' => 72],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-withdraw-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_margin_bottom',
            [
                'label'      => __('فاصله از پایین', 'golden-dashboard'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'default'    => ['size' => 18],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-withdraw-icon' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();



        $this->start_controls_section(
            'section_style_title',
            [
                'label'     => __('عنوان', 'golden-dashboard'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => ['show_title' => 'yes'],
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => __('رنگ', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#1f2937',
                'selectors' => [
                    '{{WRAPPER}} .gdb-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .gdb-title',
            ]
        );

        $this->add_responsive_control(
            'title_margin_bottom',
            [
                'label'      => __('فاصله از پایین', 'golden-dashboard'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'default'    => ['size' => 20],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();



        $this->start_controls_section(
            'section_style_balance',
            [
                'label'     => __('موجودی', 'golden-dashboard'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => ['show_balance' => 'yes'],
            ]
        );

        $this->add_control(
            'balance_label_color',
            [
                'label'     => __('رنگ برچسب موجودی', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#374151',
                'selectors' => [
                    '{{WRAPPER}} .gdb-withdraw-balance-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'balance_label_typography',
                'selector' => '{{WRAPPER}} .gdb-withdraw-balance-label',
            ]
        );

        $this->add_control(
            'balance_value_color',
            [
                'label'     => __('رنگ مبلغ موجودی', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#16a34a',
                'selectors' => [
                    '{{WRAPPER}} .gdb-withdraw-balance-value' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'balance_value_typography',
                'selector' => '{{WRAPPER}} .gdb-withdraw-balance-value',
            ]
        );

        $this->add_responsive_control(
            'balance_margin_bottom',
            [
                'label'      => __('فاصله از پایین', 'golden-dashboard'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                ],
                'default'    => ['size' => 16],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-withdraw-balance-wrap' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();



        $this->start_controls_section(
            'section_style_input',
            [
                'label' => __('ورودی مبلغ', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'input_label_color',
            [
                'label'     => __('رنگ برچسب', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#374151',
                'selectors' => [
                    '{{WRAPPER}} .gdb-input-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'input_label_typography',
                'selector' => '{{WRAPPER}} .gdb-input-label',
            ]
        );

        $this->add_control(
            'input_bg',
            [
                'label'     => __('رنگ پس‌زمینه ورودی', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .gdb-amount-input' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_color',
            [
                'label'     => __('رنگ متن ورودی', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#111827',
                'selectors' => [
                    '{{WRAPPER}} .gdb-amount-input' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'input_border',
                'selector' => '{{WRAPPER}} .gdb-amount-input',
            ]
        );

        $this->add_responsive_control(
            'input_radius',
            [
                'label'      => __('گردی گوشه ورودی', 'golden-dashboard'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default'    => [
                    'top'    => 8,
                    'right'  => 8,
                    'bottom' => 8,
                    'left'   => 8,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-amount-input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'input_padding',
            [
                'label'      => __('فاصله داخلی ورودی', 'golden-dashboard'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'    => 12,
                    'right'  => 16,
                    'bottom' => 12,
                    'left'   => 16,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-amount-input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'input_typography',
                'selector' => '{{WRAPPER}} .gdb-amount-input',
            ]
        );

        $this->add_control(
            'helper_text_color',
            [
                'label'     => __('رنگ متن راهنما', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#6b7280',
                'selectors' => [
                    '{{WRAPPER}} .gdb-input-helper' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'helper_typography',
                'selector' => '{{WRAPPER}} .gdb-input-helper',
            ]
        );

        $this->end_controls_section();



        $this->start_controls_section(
            'section_style_fee',
            [
                'label'     => __('اطلاعات کارمزد', 'golden-dashboard'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => ['show_fee_info' => 'yes'],
            ]
        );

        $this->add_control(
            'fee_bg',
            [
                'label'     => __('رنگ پس‌زمینه', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f8fafc',
                'selectors' => [
                    '{{WRAPPER}} .gdb-withdraw-fee-info' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'fee_label_color',
            [
                'label'     => __('رنگ برچسب‌ها', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#374151',
                'selectors' => [
                    '{{WRAPPER}} .gdb-withdraw-fee-info span:first-child' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'fee_amount_color',
            [
                'label'     => __('رنگ مبلغ کارمزد', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#dc2626',
                'selectors' => [
                    '{{WRAPPER}} .gdb-withdraw-fee-amount' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'net_amount_color',
            [
                'label'     => __('رنگ مبلغ قابل واریز', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#16a34a',
                'selectors' => [
                    '{{WRAPPER}} .gdb-withdraw-net-amount' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'fee_typography',
                'selector' => '{{WRAPPER}} .gdb-withdraw-fee-info',
            ]
        );

        $this->add_responsive_control(
            'fee_padding',
            [
                'label'      => __('فاصله داخلی', 'golden-dashboard'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'    => 12,
                    'right'  => 12,
                    'bottom' => 12,
                    'left'   => 12,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-withdraw-fee-info' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'fee_margin_top',
            [
                'label'      => __('فاصله از بالا', 'golden-dashboard'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 20,
                    ],
                ],
                'default'    => ['size' => 12],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-withdraw-fee-info' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();



        $this->start_controls_section(
            'section_style_submit',
            [
                'label' => __('دکمه برداشت', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'submit_typography',
                'selector' => '{{WRAPPER}} .gdb-withdraw-submit',
            ]
        );

        $this->start_controls_tabs('submit_tabs');

        $this->start_controls_tab(
            'submit_normal',
            [
                'label' => __('عادی', 'golden-dashboard'),
            ]
        );

        $this->add_control(
            'submit_bg',
            [
                'label'     => __('رنگ پس‌زمینه', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#2563eb',
                'selectors' => [
                    '{{WRAPPER}} .gdb-withdraw-submit' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'submit_color',
            [
                'label'     => __('رنگ متن', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .gdb-withdraw-submit' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'submit_hover',
            [
                'label' => __('هاور', 'golden-dashboard'),
            ]
        );

        $this->add_control(
            'submit_hover_bg',
            [
                'label'     => __('رنگ پس‌زمینه (هاور)', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#1d4ed8',
                'selectors' => [
                    '{{WRAPPER}} .gdb-withdraw-submit:hover' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'submit_hover_color',
            [
                'label'     => __('رنگ متن (هاور)', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .gdb-withdraw-submit:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'submit_border',
                'selector' => '{{WRAPPER}} .gdb-withdraw-submit',
            ]
        );

        $this->add_responsive_control(
            'submit_radius',
            [
                'label'      => __('گردی گوشه', 'golden-dashboard'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default'    => [
                    'top'    => 8,
                    'right'  => 8,
                    'bottom' => 8,
                    'left'   => 8,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-withdraw-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'submit_padding',
            [
                'label'      => __('فاصله داخلی', 'golden-dashboard'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'    => 14,
                    'right'  => 28,
                    'bottom' => 14,
                    'left'   => 28,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-withdraw-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'submit_margin_top',
            [
                'label'      => __('فاصله از بالا', 'golden-dashboard'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 40,
                    ],
                ],
                'default'    => ['size' => 20],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-withdraw-submit-wrap' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'submit_width',
            [
                'label'      => __('عرض', 'golden-dashboard'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['%', 'px'],
                'range'      => [
                    '%' => [
                        'min' => 20,
                        'max' => 100,
                    ],
                    'px' => [
                        'min' => 100,
                        'max' => 600,
                    ],
                ],
                'default'    => ['unit' => '%', 'size' => 100],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-withdraw-submit' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        if (!is_user_logged_in()) {
            if (function_exists('gdb_login_required')) {
                echo gdb_login_required();
            } else {
                echo '<p>' . __('برای برداشت از کیف پول ابتدا وارد سیستم شوید.', 'golden-dashboard') . '</p>';
            }
            return;
        }

        $settings = $this->get_settings_for_display();
        $user_id  = get_current_user_id();
        $balance  = GDB_Wallet::balance($user_id);






        $min_amount  = intval($settings['min_amount']);
        $max_amount  = intval($settings['max_amount']);
        $fee_percent = (float) get_option('gdb_withdraw_fee_percent', 0);
        $step        = intval($settings['step_amount']);


        $min_max_text = str_replace(
            ['{min}', '{max}'],
            [gdb_price($min_amount), gdb_price($max_amount)],
            $settings['min_max_text']
        );





        $min_amount_display = gdb_display_amount($min_amount);
        $max_amount_display = gdb_display_amount($max_amount);
        $step_display = gdb_display_amount($step);
        if ($step_display <= 0) {
            $step_display = 1;
        }

        $pending_requests = GDB_Wallet::get_transactions_by_type_and_status($user_id, 'withdraw_request', 'pending');
        $has_pending = !empty($pending_requests);
        $show_fee_info = ($settings['show_fee_info'] === 'yes' && $fee_percent > 0);

        include GDB_PATH . 'templates/wallet-withdraw.php';
    }
}
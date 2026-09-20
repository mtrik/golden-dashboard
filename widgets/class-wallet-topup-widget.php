<?php

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

class GDB_Wallet_Topup_Widget extends GDB_Base_Widget {

    public function get_name() {
        return 'gdb-wallet-topup';
    }

    public function get_title() {
        return __('شارژ کیف پول', 'golden-dashboard');
    }

    public function get_icon() {
        return 'eicon-credit-card';
    }

    public function get_keywords() {
        return ['wallet', 'topup', 'charge', 'payment', 'gold'];
    }

    public function get_script_depends() {
        return ['gdb-script', 'gdb-common', 'gdb-topup'];
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
                'default' => __('شارژ کیف پول', 'golden-dashboard'),
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
                    'value'   => 'fas fa-wallet',
                    'library' => 'fa-solid',
                ],
                'condition' => ['show_icon' => 'yes'],
            ]
        );

        $this->add_control(
            'min_amount',
            [
                'label'       => __('حداقل مبلغ', 'golden-dashboard'),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 10000,
                'min'         => 1000,
                'step'        => 1000,
            ]
        );

        $this->add_control(
            'max_amount',
            [
                'label'       => __('حداکثر مبلغ', 'golden-dashboard'),
                'type'        => Controls_Manager::NUMBER,
                'default'     => 50000000,
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
                'default' => __('مبلغ مورد نظر را وارد کنید', 'golden-dashboard'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label'   => __('متن دکمه پرداخت', 'golden-dashboard'),
                'type'    => Controls_Manager::TEXT,
                'default' => __('ادامه و پرداخت', 'golden-dashboard'),
            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'suggested_amount',
            [
                'label'   => __('مبلغ پیشنهادی', 'golden-dashboard'),
                'type'    => Controls_Manager::NUMBER,
                'default' => 100000,
                'min'     => 1000,
                'step'    => 1000,
            ]
        );

        $this->add_control(
            'suggested_amounts',
            [
                'label'       => __('مبالغ پیشنهادی', 'golden-dashboard'),
                'type'        => Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => [
                    ['suggested_amount' => 8000000],
                    ['suggested_amount' => 17000000],
                    ['suggested_amount' => 25000000],
                    ['suggested_amount' => 33000000],
                    ['suggested_amount' => 42000000],
                    ['suggested_amount' => 50000000],
                ],
                'title_field' => '{{ suggested_amount }}',
                'max'         => 6,
            ]
        );

        $this->add_control(
            'show_suggested_label',
            [
                'label'   => __('نمایش برچسب مبالغ پیشنهادی', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'suggested_label',
            [
                'label'     => __('برچسب مبالغ پیشنهادی', 'golden-dashboard'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('مبالغ پیشنهادی:', 'golden-dashboard'),
                'condition' => ['show_suggested_label' => 'yes'],
            ]
        );

        $this->add_control(
            'custom_amount_label',
            [
                'label'   => __('برچسب ورودی مبلغ', 'golden-dashboard'),
                'type'    => Controls_Manager::TEXT,
                'default' => __('یا مبلغ دلخواه:', 'golden-dashboard'),
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
                    '{{WRAPPER}} .gdb-topup-icon' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .gdb-topup-icon' => 'background: {{VALUE}};',
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
                    '{{WRAPPER}} .gdb-topup-icon i'   => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .gdb-topup-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .gdb-topup-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .gdb-topup-icon' => 'margin-bottom: {{SIZE}}{{UNIT}};',
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
            'section_style_suggested',
            [
                'label' => __('مبالغ پیشنهادی', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'suggested_label_color',
            [
                'label'     => __('رنگ برچسب', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#374151',
                'selectors' => [
                    '{{WRAPPER}} .gdb-suggested-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'suggested_label_typography',
                'selector' => '{{WRAPPER}} .gdb-suggested-label',
            ]
        );

        $this->add_control(
            'suggested_button_bg',
            [
                'label'     => __('رنگ پس‌زمینه دکمه‌ها', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f3f4f6',
                'selectors' => [
                    '{{WRAPPER}} .gdb-suggested-btn' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'suggested_button_hover_bg',
            [
                'label'     => __('رنگ پس‌زمینه دکمه‌ها (هاور)', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#e5e7eb',
                'selectors' => [
                    '{{WRAPPER}} .gdb-suggested-btn:hover' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'suggested_button_active_bg',
            [
                'label'     => __('رنگ پس‌زمینه دکمه انتخاب‌شده', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#2563eb',
                'selectors' => [
                    '{{WRAPPER}} .gdb-suggested-btn.active' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'suggested_button_color',
            [
                'label'     => __('رنگ متن دکمه‌ها', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#111827',
                'selectors' => [
                    '{{WRAPPER}} .gdb-suggested-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'suggested_button_active_color',
            [
                'label'     => __('رنگ متن دکمه انتخاب‌شده', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .gdb-suggested-btn.active' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'suggested_button_border',
                'selector' => '{{WRAPPER}} .gdb-suggested-btn',
            ]
        );

        $this->add_responsive_control(
            'suggested_button_radius',
            [
                'label'      => __('گردی گوشه دکمه‌ها', 'golden-dashboard'),
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
                    '{{WRAPPER}} .gdb-suggested-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'suggested_button_padding',
            [
                'label'      => __('فاصله داخلی دکمه‌ها', 'golden-dashboard'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'    => 8,
                    'right'  => 16,
                    'bottom' => 8,
                    'left'   => 16,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-suggested-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'suggested_gap',
            [
                'label'      => __('فاصله بین دکمه‌ها', 'golden-dashboard'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                ],
                'default'    => ['size' => 8],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-suggested-grid' => 'gap: {{SIZE}}{{UNIT}};',
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
            'section_style_submit',
            [
                'label' => __('دکمه پرداخت', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'submit_typography',
                'selector' => '{{WRAPPER}} .gdb-topup-submit',
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
                'default'   => '#22c55e',
                'selectors' => [
                    '{{WRAPPER}} .gdb-topup-submit' => 'background: {{VALUE}};',
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
                    '{{WRAPPER}} .gdb-topup-submit' => 'color: {{VALUE}};',
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
                'default'   => '#16a34a',
                'selectors' => [
                    '{{WRAPPER}} .gdb-topup-submit:hover' => 'background: {{VALUE}};',
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
                    '{{WRAPPER}} .gdb-topup-submit:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'submit_border',
                'selector' => '{{WRAPPER}} .gdb-topup-submit',
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
                    '{{WRAPPER}} .gdb-topup-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .gdb-topup-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .gdb-topup-submit-wrap' => 'margin-top: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .gdb-topup-submit' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        if (!is_user_logged_in()) {
            if (function_exists('gdb_login_required')) {
                echo wp_kses_post(gdb_login_required());
            } else {
                echo '<p>' . esc_html__('برای شارژ کیف پول ابتدا باید وارد سیستم شوید.', 'golden-dashboard') . '</p>';
            }
            return;
        }

        $settings = $this->get_settings_for_display();





        $min_amount = intval($settings['min_amount']);
        $max_amount = intval($settings['max_amount']);
        $step = intval($settings['step_amount']);
        $suggested_amounts = $settings['suggested_amounts'] ?? [];


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

        ?>
        <div class="gdb-card gdb-topup-widget">
            <?php if ($settings['show_icon'] === 'yes' && !empty($settings['icon']['value'])) : ?>
                <div class="gdb-topup-icon">
                    <?php Icons_Manager::render_icon($settings['icon'], ['aria-hidden' => 'true']); ?>
                </div>
            <?php endif; ?>

            <?php if ($settings['show_title'] === 'yes' && !empty($settings['title'])) : ?>
                <div class="gdb-title"><?php echo esc_html($settings['title']); ?></div>
            <?php endif; ?>

            <form method="post" action="" class="gdb-topup-form" data-ajax="1">
                <?php wp_nonce_field('gdb_wallet_topup_action', 'gdb_topup_nonce'); ?>
                <input type="hidden" name="action" value="gdb_process_topup">
                <?php
                    $gdb_current_url = gdb_get_current_url_clean();
                ?>
                <input type="hidden" name="gdb_return_url" value="<?php echo esc_url($gdb_current_url); ?>">

                <?php if (!empty($suggested_amounts) && $settings['show_suggested_label'] === 'yes') : ?>
                    <div class="gdb-suggested-label"><?php echo esc_html($settings['suggested_label']); ?></div>
                <?php endif; ?>

                <?php if (!empty($suggested_amounts)) : ?>
                    <div class="gdb-suggested-grid">
                        <?php foreach ($suggested_amounts as $item) :
                            $amount = intval($item['suggested_amount']);
                            if ($amount > 0) :




                                $amount_display = gdb_display_amount($amount);
                            ?>
                                <button type="button" class="gdb-suggested-btn" data-amount="<?php echo esc_attr($amount_display); ?>">
                                    <?php echo wp_kses_post(gdb_price($amount)); ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="gdb-input-group">
                    <label for="gdb_topup_amount" class="gdb-input-label"><?php echo esc_html($settings['custom_amount_label']); ?></label>
                    <input type="number"
                           name="topup_amount"
                           id="gdb_topup_amount"
                           class="gdb-amount-input"
                           min="<?php echo esc_attr($min_amount_display); ?>"
                           max="<?php echo esc_attr($max_amount_display); ?>"
                           step="<?php echo esc_attr($step_display); ?>"
                           placeholder="<?php echo esc_attr($settings['placeholder_text']); ?>"
                           required>
                    <div class="gdb-input-helper"><?php echo wp_kses_post($min_max_text); ?></div>
                </div>

                <div class="gdb-topup-message" style="display:none; margin-top:12px;"></div>

                <div class="gdb-topup-submit-wrap">
                    <button type="submit" class="gdb-topup-submit"><?php echo esc_html($settings['button_text']); ?></button>
                </div>
            </form>
        </div>
        <?php
    }
}
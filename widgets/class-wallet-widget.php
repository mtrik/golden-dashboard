<?php


 
if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

class GDB_Wallet_Widget extends GDB_Base_Widget
{



    public function get_name()
    {
        return 'gdb-wallet';
    }



    public function get_title()
    {
        return __('کیف پول', 'golden-dashboard');
    }



    public function get_icon()
    {
        return 'eicon-wallet';
    }



    public function get_keywords()
    {
        return ['wallet', 'balance', 'money', 'gold'];
    }



    protected function register_controls()
    {




        $this->start_controls_section(
            'content_section',
            [
                'label' => __('تنظیمات', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_CONTENT,
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
            'title',
            [
                'label'     => __('عنوان', 'golden-dashboard'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('موجودی کیف پول', 'golden-dashboard'),
                'condition' => ['show_title' => 'yes'],
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
            'show_description',
            [
                'label'   => __('نمایش توضیح', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'description',
            [
                'label'     => __('متن توضیح', 'golden-dashboard'),
                'type'      => Controls_Manager::TEXTAREA,
                'default'   => __('از موجودی کیف پول می‌توانید در پرداخت سفارش‌ها استفاده کنید.', 'golden-dashboard'),
                'condition' => ['show_description' => 'yes'],
            ]
        );



        $this->add_control(
            'show_stats',
            [
                'label'   => __('نمایش آمار سریع', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'no',
            ]
        );

        $this->add_control(
            'stats_items',
            [
                'label'     => __('آیتم‌های آماری', 'golden-dashboard'),
                'type'      => Controls_Manager::SELECT2,
                'multiple'  => true,
                'default'   => ['credit', 'debit', 'count'],
                'options'   => [
                    'credit' => __('مجموع شارژ', 'golden-dashboard'),
                    'debit'  => __('مجموع برداشت', 'golden-dashboard'),
                    'count'  => __('تعداد تراکنش‌ها', 'golden-dashboard'),
                ],
                'condition' => ['show_stats' => 'yes'],
            ]
        );



        $this->add_control(
            'show_buttons',
            [
                'label'   => __('نمایش دکمه‌های اقدام', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'no',
            ]
        );

        $this->add_control(
            'button_charge_text',
            [
                'label'     => __('متن دکمه شارژ', 'golden-dashboard'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('شارژ کیف پول', 'golden-dashboard'),
                'condition' => ['show_buttons' => 'yes'],
            ]
        );

        $this->add_control(
            'button_charge_url',
            [
                'label'     => __('لینک دکمه شارژ', 'golden-dashboard'),
                'type'      => Controls_Manager::URL,
                'default'   => [
                    'url' => '#',
                ],
                'condition' => ['show_buttons' => 'yes'],
            ]
        );

        $this->add_control(
            'button_withdraw_text',
            [
                'label'     => __('متن دکمه برداشت', 'golden-dashboard'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('برداشت', 'golden-dashboard'),
                'condition' => ['show_buttons' => 'yes'],
            ]
        );

        $this->add_control(
            'button_withdraw_url',
            [
                'label'     => __('لینک دکمه برداشت', 'golden-dashboard'),
                'type'      => Controls_Manager::URL,
                'default'   => [
                    'url' => '#',
                ],
                'condition' => ['show_buttons' => 'yes'],
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
                    '{{WRAPPER}} .gdb-wallet-icon' => 'color: {{VALUE}};',
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
                    '{{WRAPPER}} .gdb-wallet-icon' => 'background: {{VALUE}};',
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
                    '{{WRAPPER}} .gdb-wallet-icon i'   => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .gdb-wallet-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .gdb-wallet-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
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
                'default'    => ['size' => 8],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();



        $this->start_controls_section(
            'section_style_balance',
            [
                'label' => __('موجودی', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'balance_color',
            [
                'label'     => __('رنگ موجودی', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#16a34a',
                'selectors' => [
                    '{{WRAPPER}} .gdb-price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'balance_typography',
                'selector' => '{{WRAPPER}} .gdb-price',
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
                        'max' => 40,
                    ],
                ],
                'default'    => ['size' => 4],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-price' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();



        $this->start_controls_section(
            'section_style_description',
            [
                'label'     => __('توضیحات', 'golden-dashboard'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => ['show_description' => 'yes'],
            ]
        );

        $this->add_control(
            'description_color',
            [
                'label'     => __('رنگ', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#6b7280',
                'selectors' => [
                    '{{WRAPPER}} .gdb-wallet-description' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'description_typography',
                'selector' => '{{WRAPPER}} .gdb-wallet-description',
            ]
        );

        $this->end_controls_section();



        $this->start_controls_section(
            'section_style_stats',
            [
                'label'     => __('آمار سریع', 'golden-dashboard'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => ['show_stats' => 'yes'],
            ]
        );

        $this->add_control(
            'stats_text_color',
            [
                'label'     => __('رنگ متن', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#6b7280',
                'selectors' => [
                    '{{WRAPPER}} .gdb-wallet-stat' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'stats_value_color',
            [
                'label'     => __('رنگ مقادیر', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#111827',
                'selectors' => [
                    '{{WRAPPER}} .gdb-wallet-stat strong' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'stats_typography',
                'selector' => '{{WRAPPER}} .gdb-wallet-stat',
            ]
        );

        $this->add_responsive_control(
            'stats_gap',
            [
                'label'      => __('فاصله بین آیتم‌ها', 'golden-dashboard'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 30,
                    ],
                ],
                'default'    => ['size' => 12],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-wallet-stats' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'stats_divider_color',
            [
                'label'     => __('رنگ جداکننده', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#e5e7eb',
                'selectors' => [
                    '{{WRAPPER}} .gdb-wallet-stat-divider' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();



        $this->start_controls_section(
            'section_style_buttons',
            [
                'label'     => __('دکمه‌ها', 'golden-dashboard'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => ['show_buttons' => 'yes'],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'buttons_typography',
                'selector' => '{{WRAPPER}} .gdb-wallet-button',
            ]
        );

        $this->start_controls_tabs('buttons_tabs');

        
        $this->start_controls_tab(
            'buttons_normal',
            [
                'label' => __('عادی', 'golden-dashboard'),
            ]
        );

        $this->add_control(
            'button_charge_bg',
            [
                'label'     => __('رنگ پس‌زمینه شارژ', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#22c55e',
                'selectors' => [
                    '{{WRAPPER}} .gdb-wallet-button-charge' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_charge_color',
            [
                'label'     => __('رنگ متن شارژ', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .gdb-wallet-button-charge' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_withdraw_bg',
            [
                'label'     => __('رنگ پس‌زمینه برداشت', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#2563eb',
                'selectors' => [
                    '{{WRAPPER}} .gdb-wallet-button-withdraw' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_withdraw_color',
            [
                'label'     => __('رنگ متن برداشت', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .gdb-wallet-button-withdraw' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        
        $this->start_controls_tab(
            'buttons_hover',
            [
                'label' => __('هاور', 'golden-dashboard'),
            ]
        );

        $this->add_control(
            'button_charge_hover_bg',
            [
                'label'     => __('رنگ پس‌زمینه شارژ (هاور)', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#16a34a',
                'selectors' => [
                    '{{WRAPPER}} .gdb-wallet-button-charge:hover' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_withdraw_hover_bg',
            [
                'label'     => __('رنگ پس‌زمینه برداشت (هاور)', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#1d4ed8',
                'selectors' => [
                    '{{WRAPPER}} .gdb-wallet-button-withdraw:hover' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'buttons_border',
                'selector' => '{{WRAPPER}} .gdb-wallet-button',
            ]
        );

        $this->add_responsive_control(
            'buttons_radius',
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
                    '{{WRAPPER}} .gdb-wallet-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'buttons_padding',
            [
                'label'      => __('فاصله داخلی', 'golden-dashboard'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default'    => [
                    'top'    => 10,
                    'right'  => 20,
                    'bottom' => 10,
                    'left'   => 20,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-wallet-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'buttons_gap',
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
                'default'    => ['size' => 12],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-wallet-buttons' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'buttons_margin_top',
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
                'default'    => ['size' => 16],
                'selectors'  => [
                    '{{WRAPPER}} .gdb-wallet-buttons' => 'margin-top: {{SIZE}}{{UNIT}};',
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

        $balance = GDB_Wallet::balance();

        
        $stats_items = isset($settings['stats_items']) ? (array) $settings['stats_items'] : [];
        $stats_data = [];

        if ($settings['show_stats'] === 'yes') {
            if (in_array('credit', $stats_items)) {
                $stats_data['credit'] = [
                    'label' => __('مجموع شارژ', 'golden-dashboard'),
                    'value' => GDB_Wallet::total_credit(),
                ];
            }
            if (in_array('debit', $stats_items)) {
                $stats_data['debit'] = [
                    'label' => __('مجموع برداشت', 'golden-dashboard'),
                    'value' => GDB_Wallet::total_debit(),
                ];
            }
            if (in_array('count', $stats_items)) {
                $stats_data['count'] = [
                    'label' => __('تعداد تراکنش‌ها', 'golden-dashboard'),
                    'value' => GDB_Wallet::transaction_count(),
                ];
            }
        }

        ?>
        <div class="gdb-card gdb-wallet-widget">

            <?php if ($settings['show_icon'] === 'yes') : ?>
                <div class="gdb-wallet-icon">
                    <?php
                    if (!empty($settings['icon']['value'])) {
                        Icons_Manager::render_icon($settings['icon'], ['aria-hidden' => 'true']);
                    } else {
                        echo '💳';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($settings['show_title'] === 'yes') : ?>
                <div class="gdb-title">
                    <?php echo esc_html($settings['title']); ?>
                </div>
            <?php endif; ?>

            <div class="gdb-price">
                <?php echo wp_kses_post(GDB_Wallet::balance_html()); ?>
            </div>

            <?php if ($settings['show_description'] === 'yes') : ?>
                <div class="gdb-wallet-description">
                    <?php echo esc_html($settings['description']); ?>
                </div>
            <?php endif; ?>

            <?php if ($settings['show_stats'] === 'yes' && !empty($stats_data)) : ?>
                <div class="gdb-wallet-stats">
                    <?php $count = count($stats_data); $i = 0; ?>
                    <?php foreach ($stats_data as $key => $stat) : $i++; ?>
                        <div class="gdb-wallet-stat">
                            <span><?php echo esc_html($stat['label']); ?>:</span>
                            <strong>
                                <?php
                                
                                
                                
                                
                                
                                if ($key === 'count') {
                                    echo esc_html(number_format_i18n($stat['value']));
                                } else {
                                    echo wp_kses_post(gdb_price($stat['value']));
                                }
                                ?>
                            </strong>
                        </div>
                        <?php if ($i < $count) : ?>
                            <div class="gdb-wallet-stat-divider"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($settings['show_buttons'] === 'yes') : ?>
                <div class="gdb-wallet-buttons">
                    <?php if (!empty($settings['button_charge_text'])) : ?>
                        <a href="<?php echo esc_url($settings['button_charge_url']['url']); ?>"
                           class="gdb-wallet-button gdb-wallet-button-charge"
                           <?php echo $settings['button_charge_url']['is_external'] ? 'target="_blank"' : ''; ?>
                           <?php echo $settings['button_charge_url']['nofollow'] ? 'rel="nofollow"' : ''; ?>>
                            <?php echo esc_html($settings['button_charge_text']); ?>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($settings['button_withdraw_text'])) : ?>
                        <a href="<?php echo esc_url($settings['button_withdraw_url']['url']); ?>"
                           class="gdb-wallet-button gdb-wallet-button-withdraw"
                           <?php echo $settings['button_withdraw_url']['is_external'] ? 'target="_blank"' : ''; ?>
                           <?php echo $settings['button_withdraw_url']['nofollow'] ? 'rel="nofollow"' : ''; ?>>
                            <?php echo esc_html($settings['button_withdraw_text']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
        <?php

    }

}
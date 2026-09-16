<?php



if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;
use Elementor\Icons_Manager;

class GDB_Wallet_Stats_Widget extends GDB_Base_Widget
{

    public function get_name()
    {
        return 'gdb-wallet-stats';
    }

    public function get_title()
    {
        return __('آمار کیف پول', 'golden-dashboard');
    }

    public function get_icon()
    {
        return 'eicon-counter-circle';
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

        $this->add_responsive_control(
            'columns',
            [
                'label'   => __('تعداد ستون', 'golden-dashboard'),
                'type'    => Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => __('۱ ستون', 'golden-dashboard'),
                    '2' => __('۲ ستون', 'golden-dashboard'),
                    '3' => __('۳ ستون', 'golden-dashboard'),
                    '4' => __('۴ ستون', 'golden-dashboard'),
                ],
                'condition' => [
                    'layout_direction' => ['horizontal', 'auto'],
                ],
            ]
        );

        $this->add_control(
            'layout_direction',
            [
                'label'   => __('جهت نمایش', 'golden-dashboard'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'horizontal' => __('افقی (Grid)', 'golden-dashboard'),
                    'vertical'   => __('عمودی (لیست)', 'golden-dashboard'),
                    'auto'       => __('خودکار (در موبایل عمودی)', 'golden-dashboard'),
                ],
            ]
        );



        $this->add_control(
            'show_credit',
            [
                'label'   => __('نمایش مجموع شارژ', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'credit_title',
            [
                'label'     => __('عنوان مجموع شارژ', 'golden-dashboard'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('مجموع شارژ', 'golden-dashboard'),
                'condition' => ['show_credit' => 'yes'],
            ]
        );

        $this->add_control(
            'credit_icon',
            [
                'label'     => __('آیکون مجموع شارژ', 'golden-dashboard'),
                'type'      => Controls_Manager::ICONS,
                'default'   => [
                    'value'   => 'fas fa-arrow-down',
                    'library' => 'fa-solid',
                ],
                'condition' => ['show_credit' => 'yes'],
            ]
        );



        $this->add_control(
            'show_debit',
            [
                'label'   => __('نمایش مجموع برداشت', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'debit_title',
            [
                'label'     => __('عنوان مجموع برداشت', 'golden-dashboard'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('مجموع برداشت', 'golden-dashboard'),
                'condition' => ['show_debit' => 'yes'],
            ]
        );

        $this->add_control(
            'debit_icon',
            [
                'label'     => __('آیکون مجموع برداشت', 'golden-dashboard'),
                'type'      => Controls_Manager::ICONS,
                'default'   => [
                    'value'   => 'fas fa-arrow-up',
                    'library' => 'fa-solid',
                ],
                'condition' => ['show_debit' => 'yes'],
            ]
        );



        $this->add_control(
            'show_count',
            [
                'label'   => __('نمایش تعداد تراکنش', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'count_title',
            [
                'label'     => __('عنوان تعداد تراکنش', 'golden-dashboard'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('کل تراکنش‌ها', 'golden-dashboard'),
                'condition' => ['show_count' => 'yes'],
            ]
        );

        $this->add_control(
            'count_icon',
            [
                'label'     => __('آیکون تعداد تراکنش', 'golden-dashboard'),
                'type'      => Controls_Manager::ICONS,
                'default'   => [
                    'value'   => 'fas fa-list',
                    'library' => 'fa-solid',
                ],
                'condition' => ['show_count' => 'yes'],
            ]
        );



        $this->add_control(
            'show_balance',
            [
                'label'   => __('نمایش موجودی فعلی', 'golden-dashboard'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'no',
            ]
        );

        $this->add_control(
            'balance_title',
            [
                'label'     => __('عنوان موجودی', 'golden-dashboard'),
                'type'      => Controls_Manager::TEXT,
                'default'   => __('موجودی کیف پول', 'golden-dashboard'),
                'condition' => ['show_balance' => 'yes'],
            ]
        );

        $this->add_control(
            'balance_icon',
            [
                'label'     => __('آیکون موجودی', 'golden-dashboard'),
                'type'      => Controls_Manager::ICONS,
                'default'   => [
                    'value'   => 'fas fa-wallet',
                    'library' => 'fa-solid',
                ],
                'condition' => ['show_balance' => 'yes'],
            ]
        );

        $this->end_controls_section();



        $this->start_controls_section(
            'stats_style_section',
            [
                'label' => __('آمار کیف پول', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'stats_gap',
            [
                'label'      => __('فاصله کارت‌ها', 'golden-dashboard'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default'    => ['size' => 18],
                'selectors'  => [
                    '{{WRAPPER}} .wallet-stats-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        
        $this->add_control(
            'credit_icon_color',
            [
                'label'     => __('رنگ آیکون واریز', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#16a34a',
                'selectors' => [
                    '{{WRAPPER}} .stat-icon-credit' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'credit_icon_bg',
            [
                'label'     => __('پس‌زمینه آیکون واریز', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#dcfce7',
                'selectors' => [
                    '{{WRAPPER}} .stat-icon-credit' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'debit_icon_color',
            [
                'label'     => __('رنگ آیکون برداشت', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#dc2626',
                'selectors' => [
                    '{{WRAPPER}} .stat-icon-debit' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'debit_icon_bg',
            [
                'label'     => __('پس‌زمینه آیکون برداشت', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#fee2e2',
                'selectors' => [
                    '{{WRAPPER}} .stat-icon-debit' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'total_icon_color',
            [
                'label'     => __('رنگ آیکون کل تراکنش‌ها', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#2563eb',
                'selectors' => [
                    '{{WRAPPER}} .stat-icon-total' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'total_icon_bg',
            [
                'label'     => __('پس‌زمینه آیکون کل تراکنش‌ها', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#dbeafe',
                'selectors' => [
                    '{{WRAPPER}} .stat-icon-total' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'balance_icon_color',
            [
                'label'     => __('رنگ آیکون موجودی', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#9333ea',
                'selectors' => [
                    '{{WRAPPER}} .stat-icon-balance' => 'color: {{VALUE}};',
                ],
                'condition' => ['show_balance' => 'yes'],
            ]
        );

        $this->add_control(
            'balance_icon_bg',
            [
                'label'     => __('پس‌زمینه آیکون موجودی', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f3e8ff',
                'selectors' => [
                    '{{WRAPPER}} .stat-icon-balance' => 'background: {{VALUE}};',
                ],
                'condition' => ['show_balance' => 'yes'],
            ]
        );

        $this->add_control(
            'label_color',
            [
                'label'     => __('رنگ عنوان', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#6b7280',
                'selectors' => [
                    '{{WRAPPER}} .stat-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'value_color',
            [
                'label'     => __('رنگ مبلغ/عدد', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#111827',
                'selectors' => [
                    '{{WRAPPER}} .stat-value' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'count_color',
            [
                'label'     => __('رنگ تعداد تراکنش‌ها', 'golden-dashboard'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#9ca3af',
                'selectors' => [
                    '{{WRAPPER}} .stat-count' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label'      => __('اندازه آیکون', 'golden-dashboard'),
                'type'       => Controls_Manager::SLIDER,
                'range'      => [
                    'px' => [
                        'min' => 14,
                        'max' => 50,
                    ],
                ],
                'default'    => ['size' => 22],
                'selectors'  => [
                    '{{WRAPPER}} .stat-icon i'   => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .stat-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_box_size',
            [
                'label'      => __('اندازه باکس آیکون', 'golden-dashboard'),
                'type'       => Controls_Manager::SLIDER,
                'range'      => [
                    'px' => [
                        'min' => 36,
                        'max' => 80,
                    ],
                ],
                'default'    => ['size' => 48],
                'selectors'  => [
                    '{{WRAPPER}} .stat-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();



        $this->register_common_style_controls();
    }



    protected function render()
    {
        if (!is_user_logged_in()) {
            echo gdb_login_required();
            return;
        }

        global $wpdb;

        $settings = $this->get_settings_for_display();
        $user_id  = get_current_user_id();
        $table    = $wpdb->prefix . 'gd_wallet_transactions';



        $credit = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount) FROM {$table} WHERE user_id=%d AND type='credit' AND status='completed'",
                $user_id
            )
        );

        $debit = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount) FROM {$table} WHERE user_id=%d AND type='debit' AND status='completed'",
                $user_id
            )
        );

        $credit_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE user_id=%d AND type='credit' AND status='completed'",
                $user_id
            )
        );

        $debit_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE user_id=%d AND type='debit' AND status='completed'",
                $user_id
            )
        );

        $total_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE user_id=%d",
                $user_id
            )
        );

        
        $balance = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT balance_after FROM {$table} WHERE user_id=%d ORDER BY id DESC LIMIT 1",
                $user_id
            )
        );
        if (!$balance) $balance = 0;

        
        $layout_class = '';
        $columns      = (int) $settings['columns'];
        if ($columns < 1) $columns = 1;

        $direction = $settings['layout_direction'];
        if ($direction === 'vertical') {
            $layout_class = 'layout-vertical';
        } elseif ($direction === 'horizontal') {
            $layout_class = 'layout-horizontal columns-' . $columns;
        } else { 
            $layout_class = 'layout-auto columns-' . $columns;
        }

        
        $active_items = 0;
        if ($settings['show_credit'] === 'yes')  $active_items++;
        if ($settings['show_debit'] === 'yes')   $active_items++;
        if ($settings['show_count'] === 'yes')   $active_items++;
        if ($settings['show_balance'] === 'yes') $active_items++;

        
        if ($direction !== 'vertical' && $active_items > 0 && $active_items < $columns) {
            $columns = $active_items;
            
            $layout_class = str_replace('columns-' . $settings['columns'], 'columns-' . $columns, $layout_class);
        }

        ?>
        <div class="wallet-stats-grid <?php echo esc_attr($layout_class); ?>">

            <?php if ($settings['show_credit'] === 'yes') : ?>
                <div class="wallet-stat-item">
                    <div class="stat-icon stat-icon-credit">
                        <?php Icons_Manager::render_icon($settings['credit_icon'], ['aria-hidden' => 'true']); ?>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label"><?php echo esc_html($settings['credit_title']); ?></div>
                        <div class="stat-value"><?php echo gdb_price($credit); ?></div>
                        <div class="stat-count"><?php echo number_format_i18n($credit_count); ?> <?php _e('تراکنش', 'golden-dashboard'); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($settings['show_debit'] === 'yes') : ?>
                <div class="wallet-stat-item">
                    <div class="stat-icon stat-icon-debit">
                        <?php Icons_Manager::render_icon($settings['debit_icon'], ['aria-hidden' => 'true']); ?>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label"><?php echo esc_html($settings['debit_title']); ?></div>
                        <div class="stat-value"><?php echo gdb_price($debit); ?></div>
                        <div class="stat-count"><?php echo number_format_i18n($debit_count); ?> <?php _e('تراکنش', 'golden-dashboard'); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($settings['show_count'] === 'yes') : ?>
                <div class="wallet-stat-item">
                    <div class="stat-icon stat-icon-total">
                        <?php Icons_Manager::render_icon($settings['count_icon'], ['aria-hidden' => 'true']); ?>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label"><?php echo esc_html($settings['count_title']); ?></div>
                        <div class="stat-value"><?php echo number_format_i18n($total_count); ?></div>
                        <div class="stat-count"><?php _e('تراکنش', 'golden-dashboard'); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($settings['show_balance'] === 'yes') : ?>
                <div class="wallet-stat-item">
                    <div class="stat-icon stat-icon-balance">
                        <?php Icons_Manager::render_icon($settings['balance_icon'], ['aria-hidden' => 'true']); ?>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label"><?php echo esc_html($settings['balance_title']); ?></div>
                        <div class="stat-value"><?php echo gdb_price($balance); ?></div>
                        <div class="stat-count"><?php _e('موجودی فعلی', 'golden-dashboard'); ?></div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
        <?php
    }
}
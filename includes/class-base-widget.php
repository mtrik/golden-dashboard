<?php

if (!defined('ABSPATH')) {
    exit;
}




if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

abstract class GDB_Base_Widget extends Widget_Base
{

    public function get_categories()
    {
        return ['golden-dashboard'];
    }

    public function get_keywords()
    {
        return [
            'gold',
            'wallet',
            'dashboard'
        ];
    }

    public function get_style_depends()
    {
        return [
            'gdb-style',
            'gdb-widgets'
        ];
    }

    public function get_script_depends()
    {
        return [
            'gdb-script'
        ];
    }



    protected function register_common_style_controls()
    {

        $this->start_controls_section(
            'gdb_style_card',
            [
                'label' => __('کارت', 'golden-dashboard'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_bg',
            [
                'label' => __('رنگ پس‌زمینه', 'golden-dashboard'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gdb-card' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(

            Group_Control_Border::get_type(),

            [

                'name' => 'card_border',

                'selector' => '{{WRAPPER}} .gdb-card',

            ]

        );

        $this->add_group_control(

            Group_Control_Box_Shadow::get_type(),

            [

                'name' => 'card_shadow',

                'selector' => '{{WRAPPER}} .gdb-card',

            ]

        );

        $this->add_responsive_control(

            'card_padding',

            [

                'label' => __('Padding', 'golden-dashboard'),

                'type' => Controls_Manager::DIMENSIONS,

                'selectors' => [

                    '{{WRAPPER}} .gdb-card' =>
                        'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',

                ],

            ]

        );

        $this->end_controls_section();




        $this->start_controls_section(

            'gdb_style_title',

            [

                'label' => __('عنوان', 'golden-dashboard'),

                'tab' => Controls_Manager::TAB_STYLE,

            ]

        );

        $this->add_control(

            'title_color',

            [

                'label' => __('رنگ', 'golden-dashboard'),

                'type' => Controls_Manager::COLOR,

                'selectors' => [

                    '{{WRAPPER}} .gdb-title' => 'color:{{VALUE}};',

                ],

            ]

        );

        $this->add_group_control(

            Group_Control_Typography::get_type(),

            [

                'name' => 'title_typography',

                'selector' => '{{WRAPPER}} .gdb-title',

            ]

        );

        $this->end_controls_section();




        $this->start_controls_section(

            'gdb_style_price',

            [

                'label' => __('مبلغ', 'golden-dashboard'),

                'tab' => Controls_Manager::TAB_STYLE,

            ]

        );

        $this->add_control(

            'price_color',

            [

                'label' => __('رنگ مبلغ', 'golden-dashboard'),

                'type' => Controls_Manager::COLOR,

                'default' => '#16a34a',

                'selectors' => [

                    '{{WRAPPER}} .gdb-price' => 'color:{{VALUE}};',

                ],

            ]

        );

        $this->add_group_control(

            Group_Control_Typography::get_type(),

            [

                'name' => 'price_typography',

                'selector' => '{{WRAPPER}} .gdb-price',

            ]

        );

        $this->end_controls_section();

    }

}
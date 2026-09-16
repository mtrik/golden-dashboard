<?php

if (!defined('ABSPATH')) {
    exit;
}

class GDB_Elementor
{


    private $widgets = array(
        'wallet',
        'wallet-transactions',
        'wallet-stats',
        'wallet-topup',
        'wallet-withdraw', 
        'gold-wallet',
        'gold-balance',
    );

    public function __construct()
    {
        add_action(
            'elementor/elements/categories_registered',
            array($this, 'register_category')
        );

        add_action(
            'elementor/widgets/register',
            array($this, 'register_widgets')
        );
    }



    public function register_category($elements_manager)
    {
        $elements_manager->add_category(
            'golden-dashboard',
            array(
                'title' => __('گلدن داشبورد', 'golden-dashboard'),
                'icon'  => 'fa fa-coins',
            )
        );
    }



    public function register_widgets($widgets_manager)
    {


        $base = GDB_PATH . 'includes/class-base-widget.php';

        if (file_exists($base) && !class_exists('GDB_Base_Widget')) {
            require_once $base;
        }



        if (!class_exists('GDB_Base_Widget')) {
            return;
        }

        foreach ($this->widgets as $widget) {

            $file = GDB_PATH . 'widgets/class-' . $widget . '-widget.php';

            if (!file_exists($file)) {
                continue;
            }

            require_once $file;

            $class = 'GDB_' . str_replace('-', '_', ucwords($widget, '-')) . '_Widget';

            if (class_exists($class)) {
                $widgets_manager->register(new $class());
            }
        }
    }
}
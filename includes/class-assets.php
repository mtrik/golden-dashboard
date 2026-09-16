<?php

if (!defined('ABSPATH')) {
    exit;
}

class GDB_Assets
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'frontend_assets'));
        add_action('elementor/frontend/after_enqueue_styles', array($this, 'elementor_styles'));
        add_action('elementor/frontend/after_register_scripts', array($this, 'elementor_scripts'));
    }

    
    public function frontend_assets()
    {
        wp_register_style(
            'gdb-widgets',
            GDB_URL . 'assets/css/widgets.css',
            array(),
            GDB_VERSION
        );

        wp_register_script(
            'gdb-script',
            GDB_URL . 'assets/js/script.js',
            array('jquery'),
            GDB_VERSION,
            true
        );

        wp_localize_script(
            'gdb-script',
            'gdb',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'user'    => get_current_user_id(),
                'nonce'   => wp_create_nonce('gdb_nonce')
            )
        );

        wp_register_script(
            'gdb-common',
            GDB_URL . 'assets/js/gdb-common.js',
            array('jquery'),
            GDB_VERSION,
            true
        );

        wp_register_script(
            'gdb-withdraw',
            GDB_URL . 'assets/js/withdraw.js',
            array('jquery', 'gdb-common'),
            GDB_VERSION,
            true
        );

        wp_register_script(
            'gdb-topup',
            GDB_URL . 'assets/js/topup.js',
            array('jquery', 'gdb-common'),
            GDB_VERSION,
            true
        );

        wp_register_script(
            'gdb-gold-wallet',
            GDB_URL . 'assets/js/gold-wallet.js',
            array('jquery', 'gdb-common', 'gdb-script'),
            GDB_VERSION,
            true
        );

        $currency_symbol = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : 'تومان';
        $thousand_sep = function_exists('wc_get_price_thousand_separator') ? wc_get_price_thousand_separator() : ',';
        $decimal_sep = function_exists('wc_get_price_decimal_separator') ? wc_get_price_decimal_separator() : '.';

        wp_localize_script(
            'gdb-withdraw',
            'gdb_withdraw',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('gdb_withdraw_nonce'),
                'currency_symbol' => $currency_symbol,
                'thousand_separator' => $thousand_sep,
                'decimal_separator' => $decimal_sep,
            )
        );

        global $post;
        $has_shortcode = false;
        if ($post) {
            $has_shortcode = has_shortcode($post->post_content, 'gold_wallet_balance') ||
                             has_shortcode($post->post_content, 'gold_wallet_card') ||
                             has_shortcode($post->post_content, 'gold_wallet_transactions');
        }

        $is_elementor_editor = (isset($_GET['elementor-preview']) || isset($_GET['elementor']) || (isset($_REQUEST['action']) && $_REQUEST['action'] === 'elementor'));

        $has_elementor_widget = false;
        if (!$has_shortcode && !$is_elementor_editor && !is_admin() && class_exists('\Elementor\Plugin')) {
            $doc = \Elementor\Plugin::$instance->documents->get_current();
            if ($doc) {
                $data = $doc->get_elements_data();
                if (!empty($data)) {
                    $widget_names = ['gdb-wallet', 'gdb-wallet-transactions', 'gdb-wallet-stats', 'gdb-wallet-topup', 'gdb-wallet-withdraw', 'gdb-gold-wallet', 'gdb-gold-balance'];
                    $has_elementor_widget = $this->search_widgets_in_elementor_data($data, $widget_names);
                }
            }
        }

        if (!$has_shortcode && !$is_elementor_editor && !is_admin() && !$has_elementor_widget) {
            return;
        }

        $this->enqueue_all_scripts();
    }

    
    private function enqueue_all_scripts() {
        wp_enqueue_style('gdb-widgets');
        wp_enqueue_script('gdb-script');
        wp_enqueue_script('gdb-common');
        wp_enqueue_script('gdb-withdraw');
        wp_enqueue_script('gdb-topup');
        wp_enqueue_script('gdb-gold-wallet');
    }

    private function search_widgets_in_elementor_data($elements_data, $widget_names)
    {
        foreach ($elements_data as $element) {
            if (isset($element['widgetType']) && in_array($element['widgetType'], $widget_names, true)) {
                return true;
            }
            if (isset($element['elements']) && is_array($element['elements'])) {
                if ($this->search_widgets_in_elementor_data($element['elements'], $widget_names)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function elementor_styles()
    {
        wp_enqueue_style('gdb-widgets');
    }

    public function elementor_scripts()
    {
        wp_enqueue_script('gdb-script');
    }
}
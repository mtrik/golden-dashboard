<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GDB_Shortcodes {

    public function __construct() {
        add_shortcode( 'gold_wallet_balance', array( $this, 'wallet_balance' ) );
        add_shortcode( 'gold_wallet_card', array( $this, 'wallet_card' ) );
        add_shortcode( 'gold_wallet_transactions', array( $this, 'wallet_transactions' ) );
    }

    public function wallet_balance( $atts = array(), $content = '', $tag = '' ) {
        if ( ! is_user_logged_in() ) {
            return '';
        }

        $atts = shortcode_atts(
            array(
                'user_id' => 0,
            ),
            $atts,
            $tag
        );

        $user_id = absint( $atts['user_id'] ) ?: get_current_user_id();
        return GDB_Wallet::get_formatted_balance( $user_id );
    }

    public function wallet_card( $atts = array(), $content = '', $tag = '' ) {
        if ( ! is_user_logged_in() ) {
            return '<div class="wallet-login-required">' . esc_html__( 'ابتدا وارد حساب کاربری شوید.', 'golden-dashboard' ) . '</div>';
        }

        $atts = shortcode_atts(
            array(
                'show_button'   => 'yes',
                'title'         => '',
                'description'   => '',
                'button_text'   => '',
            ),
            $atts,
            $tag
        );

        $settings = array(
            'show_button' => in_array( strtolower( (string) $atts['show_button'] ), array( 'yes', '1', 'true' ), true ) ? 'yes' : 'no',
            'title'       => $atts['title'],
            'description' => $atts['description'],
            'button_text' => $atts['button_text'],
        );

        ob_start();
        include GDB_PATH . 'templates/wallet-card.php';
        return ob_get_clean();
    }

    public function wallet_transactions( $atts = array(), $content = '', $tag = '' ) {
        if ( ! is_user_logged_in() ) {
            return '<div class="wallet-login-required">' . esc_html__( 'ابتدا وارد حساب کاربری شوید.', 'golden-dashboard' ) . '</div>';
        }

        $atts = shortcode_atts(
            array(
                'limit' => 5,
            ),
            $atts,
            $tag
        );

        $limit = max( 1, absint( $atts['limit'] ) );
        $transactions = GDB_Wallet::get_transactions( get_current_user_id(), $limit );

        if ( empty( $transactions ) ) {
            return '<div class="gdb-empty">' . esc_html__( 'هیچ تراکنشی یافت نشد.', 'golden-dashboard' ) . '</div>';
        }

        $item_settings = array(
            'show_icon'          => true,
            'show_amount'        => true,
            'show_date'          => true,
            'show_description'   => true,
            'show_balance_after' => true,
            'show_status'        => true,
            'show_admin_info'    => true,
            'show_fee'           => true,
        );

        ob_start();
        ?>
        <div class="gdb-transactions-wrapper">
            <div class="gdb-transactions-list">
                <?php
                foreach ( $transactions as $tx ) {
                    echo wp_kses_post( gdb_render_transaction_item( $tx, $item_settings ) );
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
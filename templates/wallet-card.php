<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings = isset( $settings ) && is_array( $settings ) ? $settings : array();

$title       = ! empty( $settings['title'] ) ? $settings['title'] : __( 'موجودی فعلی کیف پول', 'golden-dashboard' );
$description = ! empty( $settings['description'] ) ? $settings['description'] : __( 'از این موجودی می‌توانید در هنگام خرید استفاده کنید.', 'golden-dashboard' );
$button_text = ! empty( $settings['button_text'] ) ? $settings['button_text'] : __( 'شارژ کیف پول', 'golden-dashboard' );
$show_button = isset( $settings['show_button'] ) ? $settings['show_button'] : 'yes';

$user_id = get_current_user_id();
$balance = GDB_Wallet::get_formatted_balance( $user_id );
$account_url = GDB_Wallet::get_account_url();
?>

<div class="gdb-wallet-container">
    <div class="wallet-balance-card profile-section">
        <div class="wallet-balance-card__inner">
            <div class="wallet-balance-card__icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M21 7.28V5C21 3.9 20.1 3 19 3H5C3.89 3 3 3.9 3 5V19C3 20.1 3.89 21 5 21H19C20.1 21 21 20.1 21 19V16.72C21.59 16.37 22 15.74 22 15V9C22 8.26 21.59 7.63 21 7.28ZM20 9V15H13V9H20ZM5 19V5H19V7H13C11.9 7 11 7.9 11 9V15C11 16.1 11.9 17 13 17H19V19H5Z" fill="currentColor"/>
                    <circle cx="16" cy="12" r="1.5" fill="currentColor"/>
                </svg>
            </div>

            <div class="wallet-balance-card__content">
                <div class="wallet-balance-card__label">
                    <?php echo esc_html( $title ); ?>
                </div>

                <div class="wallet-balance-card__amount">
                    <?php echo wp_kses_post( $balance ); ?>
                </div>

                <div class="wallet-balance-card__description">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V11H13V17ZM13 9H11V7H13V9Z" fill="currentColor"/>
                    </svg>
                    <?php echo esc_html( $description ); ?>
                </div>

                <?php if ( 'yes' === $show_button ) : ?>
                    <div class="wallet-balance-card__actions">
                        <a href="<?php echo esc_url( $account_url ); ?>" class="button alt wp-element-button gdb-wallet-topup-btn">
                            <?php echo esc_html( $button_text ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
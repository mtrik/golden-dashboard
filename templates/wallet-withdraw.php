<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="gdb-card gdb-withdraw-widget">
    <?php if ($settings['show_icon'] === 'yes' && !empty($settings['icon']['value'])) : ?>
        <div class="gdb-withdraw-icon">
            <?php
            if (class_exists('\Elementor\Icons_Manager')) {
                \Elementor\Icons_Manager::render_icon($settings['icon'], ['aria-hidden' => 'true']);
            } else {
                echo '<span class="gdb-icon-fallback">💰</span>';
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if ($settings['show_title'] === 'yes' && !empty($settings['title'])) : ?>
        <div class="gdb-title"><?php echo esc_html($settings['title']); ?></div>
    <?php endif; ?>

    <?php if ($settings['show_balance'] === 'yes') : ?>
        <div class="gdb-withdraw-balance-wrap">
            <span class="gdb-withdraw-balance-label"><?php echo esc_html($settings['balance_label']); ?></span>
            <span class="gdb-withdraw-balance-value"><?php echo GDB_Wallet::balance_html($user_id); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($has_pending) : ?>
        <div class="gdb-withdraw-pending-notice">
            <p><?php _e('شما درخواست‌های برداشت در حال بررسی دارید. پس از تأیید مدیر، مبلغ از کیف پول کسر خواهد شد.', 'golden-dashboard'); ?></p>
            <?php foreach ($pending_requests as $req) : ?>
                <p><small><?php echo sprintf(__('کد پیگیری: %s - مبلغ: %s', 'golden-dashboard'), esc_html($req->tracking_code), gdb_price($req->amount)); ?></small></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="gdb-withdraw-form" data-ajax="1" data-min-amount="<?php echo esc_attr($min_amount); ?>" data-max-amount="<?php echo esc_attr($max_amount); ?>">
        <?php wp_nonce_field('gdb_withdraw_nonce', 'gdb_withdraw_nonce'); ?>
        <input type="hidden" name="action" value="gdb_process_withdraw">

        <div class="gdb-input-group">
            <label for="gdb_withdraw_amount" class="gdb-input-label">
                <?php _e('مبلغ برداشت:', 'golden-dashboard'); ?>
            </label>
            <input type="number"
                   name="amount"
                   class="gdb-amount-input gdb-withdraw-amount"
                   min="<?php echo esc_attr($min_amount_display); ?>"
                   max="<?php echo esc_attr($max_amount_display); ?>"
                   step="<?php echo esc_attr($step_display); ?>"
                   placeholder="<?php echo esc_attr($settings['placeholder_text']); ?>"
                   required>
            <div class="gdb-input-helper"><?php echo wp_kses_post($min_max_text); ?></div>
        </div>

        <?php if ($show_fee_info) : ?>
            <div class="gdb-withdraw-fee-info" style="display: none;">
                <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 4px;">
                    <span><?php printf(__('کارمزد (%.2f%%):', 'golden-dashboard'), $fee_percent); ?></span>
                    <span class="gdb-withdraw-fee-amount" style="font-weight: 600; color: #dc2626;">0</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 15px; font-weight: 700;">
                    <span><?php _e('مبلغ قابل واریز:', 'golden-dashboard'); ?></span>
                    <span class="gdb-withdraw-net-amount" style="color: #16a34a;">0</span>
                </div>
            </div>
        <?php endif; ?>

        <div class="gdb-withdraw-submit-wrap">
            <button type="submit" class="gdb-withdraw-submit" <?php echo $has_pending ? 'disabled' : ''; ?>>
                <?php echo $has_pending ? __('در انتظار بررسی', 'golden-dashboard') : esc_html($settings['button_text']); ?>
            </button>
        </div>

        <div class="gdb-withdraw-message" style="display:none; margin-top:12px;"></div>
    </form>
</div>

<?php if ($show_fee_info) : ?>
<script>
(function($) {
    'use strict';
    $(document).ready(function() {
        var feePercent = <?php echo floatval($fee_percent); ?>;
        var $input = $('.gdb-withdraw-amount');
        var $feeInfo = $('.gdb-withdraw-fee-info');
        var $feeAmount = $('.gdb-withdraw-fee-amount');
        var $netAmount = $('.gdb-withdraw-net-amount');
        var currencySymbol = (typeof gdb_withdraw !== 'undefined' && gdb_withdraw.currency_symbol) ? gdb_withdraw.currency_symbol : 'تومان';
        var thousandSep = (typeof gdb_withdraw !== 'undefined' && gdb_withdraw.thousand_separator) ? gdb_withdraw.thousand_separator : ',';
        var decimalSep = (typeof gdb_withdraw !== 'undefined' && gdb_withdraw.decimal_separator) ? gdb_withdraw.decimal_separator : '.';

        function formatNumber(num) {
            if (isNaN(num)) return '0';
            var parts = num.toFixed(0).split('.');
            var intPart = parts[0];
            var decPart = parts[1] ? decimalSep + parts[1] : '';
            var formatted = '';
            var counter = 0;
            for (var i = intPart.length - 1; i >= 0; i--) {
                formatted = intPart[i] + formatted;
                counter++;
                if (counter % 3 === 0 && i !== 0) {
                    formatted = thousandSep + formatted;
                }
            }
            return formatted + decPart;
        }

        if (feePercent <= 0) {
            return;
        }

        $input.on('input', function() {
            var amount = parseFloat($(this).val()) || 0;
            if (amount > 0) {
                var fee = amount * (feePercent / 100);
                var net = amount - fee;
                if (net < 0) net = 0;
                $feeAmount.text(formatNumber(fee) + ' ' + currencySymbol);
                $netAmount.text(formatNumber(net) + ' ' + currencySymbol);
                $feeInfo.show();
            } else {
                $feeInfo.hide();
            }
        });
    });
})(jQuery);
</script>
<?php endif; ?>
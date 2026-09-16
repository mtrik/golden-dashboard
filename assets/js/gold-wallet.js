(function ($) {
    'use strict';

    $(document).ready(function () {

        $(document).on('click', '.gdb-gold-wallet-widget .gdb-suggested-btn', function (e) {
            e.preventDefault();
            var amount = $(this).data('amount');
            var $form = $(this).closest('.gdb-gold-wallet-form');
            $form.find('.gdb-gold-wallet-quantity').val(amount).trigger('input');
            $form.find('.gdb-suggested-btn').removeClass('active');
            $(this).addClass('active');
        });

        $(document).on('input', '.gdb-gold-wallet-quantity', function () {
            var $input = $(this);
            var $widget = $input.closest('.gdb-gold-wallet-widget');
            var $warning = $input.closest('.gdb-input-group').find('.gdb-quantity-warning');
            $widget.find('.gdb-suggested-btn').removeClass('active');

            var rawVal = parseFloat($input.val());
            var minVal = parseFloat($input.attr('min'));
            var maxVal = $input.attr('max') ? parseFloat($input.attr('max')) : null;
            var clamped = false;
            var warningText = '';

            if (!isNaN(rawVal) && maxVal !== null && rawVal > maxVal) {
                $input.val(maxVal);
                clamped = true;
                warningText = gdb.i18n && gdb.i18n.maxQuantity
                    ? gdb.i18n.maxQuantity.replace('%s', maxVal)
                    : 'حداکثر مقدار مجاز ' + maxVal + ' است.';
            } else if (!isNaN(rawVal) && !isNaN(minVal) && rawVal < minVal && $input.val() !== '') {
                warningText = gdb.i18n && gdb.i18n.minQuantity
                    ? gdb.i18n.minQuantity.replace('%s', minVal)
                    : 'حداقل مقدار مجاز ' + minVal + ' است.';
            }

            if (warningText) {
                clearTimeout($warning.data('gdb-hide-timer'));
                $warning.text(warningText).stop(true, true).fadeIn(120);
                var hideTimer = setTimeout(function () {
                    $warning.fadeOut(300);
                }, 2500);
                $warning.data('gdb-hide-timer', hideTimer);
            }

            clearTimeout($widget.data('gdb-price-debounce-timer'));
            var timer = setTimeout(function () {
                updateLivePrice($widget);
            }, 350);
            $widget.data('gdb-price-debounce-timer', timer);
        });

        $(document).on('change', '.gdb-gold-wallet-use-balance', function () {
            var $widget = $(this).closest('.gdb-gold-wallet-widget');
            updateLivePrice($widget);
        });

        function updateSplitInfo($widget, totalDisplay) {
            var $checkbox = $widget.find('.gdb-gold-wallet-use-balance');
            var $splitInfo = $widget.find('.gdb-gold-wallet-split-info');
            if (!$checkbox.length || !$splitInfo.length) {
                return;
            }

            if (!$checkbox.is(':checked') || !totalDisplay || totalDisplay <= 0) {
                $splitInfo.hide().text('');
                return;
            }

            var walletBalance = parseFloat($widget.data('wallet-balance')) || 0;
            var totalToman = parseFloat(totalDisplay);
            var walletPortionToman = Math.min(walletBalance, totalToman);
            var gatewayPortionToman = Math.max(0, totalToman - walletPortionToman);

            var walletDisplay = gdbDisplayAmount(walletPortionToman);
            var gatewayDisplay = gdbDisplayAmount(gatewayPortionToman);

            walletDisplay = Math.round(walletDisplay);
            gatewayDisplay = Math.round(gatewayDisplay);

            var walletFormatted = walletDisplay.toLocaleString('fa-IR');
            var gatewayFormatted = gatewayDisplay.toLocaleString('fa-IR');

            var text = 'از کیف پول: ' + walletFormatted + ' — از درگاه پرداخت: ' + gatewayFormatted;
            $splitInfo.text(text).show();
        }

        function updateLivePrice($widget) {
            var typeId = $widget.data('type-id');
            var quantity = parseFloat($widget.find('.gdb-gold-wallet-quantity').val()) || 0;
            var $total = $widget.find('.gdb-gold-wallet-total-price');
            var nonce = $widget.find('[name="gdb_gold_wallet_nonce"]').val();

            if (quantity <= 0) {
                $total.text('-');
                updateSplitInfo($widget, 0);
                return;
            }

            $.ajax({
                url: gdb.ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'gdb_gold_wallet_get_price',
                    type_id: typeId,
                    quantity: quantity,
                    nonce: nonce
                }
            }).done(function (response) {
                var currentQuantity = parseFloat($widget.find('.gdb-gold-wallet-quantity').val()) || 0;
                if (currentQuantity !== quantity) {
                    return;
                }
                if (response.success) {
                    var totalToman = response.data.total;
                    $total.text(response.data.total_formatted);
                    $widget.find('.gdb-gold-wallet-unit-price').text(response.data.price_formatted);
                    updateSplitInfo($widget, totalToman);
                }
            });
        }

        function gdbDisplayAmount(amount) {
            if (typeof gdb !== 'undefined' && gdb.currency === 'IRR') {
                return amount * 10;
            }
            return amount;
        }

        $(document).on('submit', '.gdb-gold-wallet-form[data-ajax="1"]', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $widget = $form.closest('.gdb-gold-wallet-widget');
            var $submit = $form.find('.gdb-gold-wallet-submit');
            var $message = $form.find('.gdb-gold-wallet-message');

            var quantity = $form.find('.gdb-gold-wallet-quantity').val();
            if (!quantity || parseFloat(quantity) <= 0) {
                GDBCommon.showMessage($message, 'لطفاً مقدار معتبری وارد کنید.', 'error');
                return;
            }

            if (!$submit.data('original-text')) {
                $submit.data('original-text', $submit.text());
            }
            $submit.prop('disabled', true).text('در حال پردازش...');
            $message.hide().removeClass('error success').text('');

            $.ajax({
                url: gdb.ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: $form.serialize() + '&action=gdb_process_gold_purchase'
            })
            .done(function (response) {
                if (response.success) {
                    if (response.data.mode === 'gateway' && response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                        return;
                    }

                    GDBCommon.showResultPopup({
                        success: true,
                        title: 'خرید با موفقیت انجام شد',
                        message: 'خرید شما از کیف پول با موفقیت ثبت و تکمیل شد.',
                        tracking_code: response.data.tracking_code,
                        rows: [
                            { label: 'مقدار خریداری‌شده', value: response.data.quantity + ' ' + response.data.unit_label },
                            { label: 'مبلغ پرداختی', value: response.data.rial_amount_formatted || response.data.rial_amount }
                        ]
                    });

                    $widget.find('.gdb-gold-wallet-my-balance').text(response.data.gold_balance + ' ' + response.data.unit_label);
                    $form.find('.gdb-gold-wallet-quantity').val('');
                    $widget.find('.gdb-gold-wallet-total-price').text('-');
                    $submit.prop('disabled', false).text($submit.data('original-text'));

                    GDBCommon.refreshTransactions();
                    GDBCommon.refreshGoldBalances();
                    if (response.data.cash_balance_formatted) {
                        GDBCommon.updateAllBalances(response.data.cash_balance_formatted, response.data.cash_balance_display);
                    }
                } else {
                    GDBCommon.showMessage($message, (response.data && response.data.message) || 'خطا در ثبت خرید.', 'error');
                    $submit.prop('disabled', false).text($submit.data('original-text'));
                }
            })
            .fail(function () {
                GDBCommon.showMessage($message, 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
                $submit.prop('disabled', false).text($submit.data('original-text'));
            });
        });

        $('.gdb-gold-wallet-widget').each(function () {
            var $widget = $(this);
            var $checkbox = $widget.find('.gdb-gold-wallet-use-balance');
            if ($checkbox.length) {
                if ($checkbox.is(':checked')) {
                    updateLivePrice($widget);
                }
            }
        });

    });

})(jQuery);
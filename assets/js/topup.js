(function ($) {
    'use strict';

    $(document).ready(function () {

        $(document).on('click', '.gdb-topup-form .gdb-suggested-btn', function (e) {
            e.preventDefault();
            var amount = $(this).data('amount');
            var $form = $(this).closest('.gdb-topup-form');
            var $input = $form.find('.gdb-amount-input');
            $input.val(amount).trigger('input');
            $form.find('.gdb-suggested-btn').removeClass('active');
            $(this).addClass('active');
        });

        $(document).on('input', '.gdb-topup-form .gdb-amount-input', function () {
            if ($(this).val() !== '') {
                $(this).closest('.gdb-topup-form').find('.gdb-suggested-btn').removeClass('active');
            }
        });

        $(document).on('submit', '.gdb-topup-form[data-ajax="1"]', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $submit = $form.find('.gdb-topup-submit');
            var $message = $form.find('.gdb-topup-message');

            var amount = $form.find('.gdb-amount-input').val();
            if (!amount || parseFloat(amount) <= 0) {
                GDBCommon.showMessage($message, 'لطفاً مبلغ معتبری وارد کنید.', 'error');
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
                data: $form.serialize()
            })
            .done(function (response) {
                if (response.success && response.data.redirect_url) {
                    window.location.href = response.data.redirect_url;
                } else {
                    GDBCommon.showMessage($message, (response.data && response.data.message) || 'خطا در پردازش درخواست.', 'error');
                    $submit.prop('disabled', false).text($submit.data('original-text'));
                }
            })
            .fail(function () {
                GDBCommon.showMessage($message, 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
                $submit.prop('disabled', false).text($submit.data('original-text'));
            });
        });

    });

})(jQuery);

(function($) {
    'use strict';

    $(document).ready(function() {

        $(document).on('submit', '.gdb-withdraw-form[data-ajax="1"]', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submit = $form.find('.gdb-withdraw-submit');
            var $message = $form.find('.gdb-withdraw-message');

            $submit.prop('disabled', true).text('در حال پردازش...');
            $message.hide().removeClass('error success').text('');

            var amount = $form.find('.gdb-withdraw-amount').val();
            if (!amount || parseFloat(amount) <= 0) {
                GDBCommon.showMessage($message, 'لطفاً مبلغ معتبری وارد کنید.', 'error');
                $submit.prop('disabled', false).text($submit.data('original-text') || 'درخواست برداشت');
                return;
            }

            var minAmount = $form.data('min-amount');
            var maxAmount = $form.data('max-amount');

            $.ajax({
                url: gdb_withdraw.ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'gdb_process_withdraw',
                    amount: amount,
                    min_amount: minAmount,
                    max_amount: maxAmount,
                    nonce: gdb_withdraw.nonce
                }
            })
            .done(function(response) {
                if (response.success) {
                    GDBCommon.showResultPopup({
                        success: true,
                        title: 'درخواست برداشت ثبت شد',
                        message: response.data.message,
                        tracking_code: response.data.tracking_code,
                        rows: [
                            { label: 'مبلغ درخواستی', value: response.data.amount_formatted },
                            { label: 'موجودی فعلی', value: response.data.balance_formatted }
                        ],
                        status_row: '<tr><td>وضعیت</td><td><span style="color:#d97706; font-weight:bold;">در انتظار تأیید</span></td></tr>'
                    });
                    $form.find('.gdb-withdraw-amount').val('');
                    GDBCommon.showMessage($message, response.data.message, 'success');
                    GDBCommon.updateAllBalances(response.data.balance_formatted, response.data.balance_display);
                    $submit.prop('disabled', true).text('در انتظار بررسی');
                    refreshTransactions();
                } else {
                    GDBCommon.showMessage($message, response.data.message || 'خطا در انجام برداشت.', 'error');
                    $submit.prop('disabled', false).text($submit.data('original-text') || 'درخواست برداشت');
                }
            })
            .fail(function() {
                GDBCommon.showMessage($message, 'خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.', 'error');
                $submit.prop('disabled', false).text($submit.data('original-text') || 'درخواست برداشت');
            });
        });

        $('.gdb-withdraw-submit').each(function() {
            $(this).data('original-text', $(this).text());
            if ($(this).prop('disabled')) {
                $(this).text('در انتظار بررسی');
            }
        });

        function refreshTransactions() {
            var $transactionsWrapper = $('.gdb-transactions-wrapper');
            if (!$transactionsWrapper.length) return;

            $.ajax({
                url: gdb_withdraw.ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'gdb_get_recent_transactions',
                    nonce: gdb_withdraw.nonce,
                    limit: 10
                }
            })
            .done(function(response) {
                if (response.success) {
                    var $results = $transactionsWrapper.find('.gdb-history-results').first();
                    if ($results.length) {
                        $results.html(response.data.html);
                    } else {
                        $transactionsWrapper.html(response.data.html);
                    }

                    if (response.data.balance_formatted) {
                        GDBCommon.updateAllBalances(response.data.balance_formatted, response.data.balance_display);
                    }

                    if (response.data.has_pending) {
                        $('.gdb-withdraw-submit').prop('disabled', true).text('در انتظار بررسی');
                        if (!$('.gdb-withdraw-pending-notice').length) {
                            var noticeHtml = '<div class="gdb-withdraw-pending-notice">' +
                                '<p>شما درخواست‌های برداشت در حال بررسی دارید. پس از تأیید مدیر، مبلغ از کیف پول کسر خواهد شد.</p>' +
                                '</div>';
                            $('.gdb-withdraw-widget').prepend(noticeHtml);
                        }
                    } else {
                        $('.gdb-withdraw-submit').prop('disabled', false).text('درخواست برداشت');
                        $('.gdb-withdraw-pending-notice').remove();
                    }
                }
            })
            .fail(function() {
            });
        }

    });

})(jQuery);

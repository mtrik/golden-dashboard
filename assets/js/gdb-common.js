(function ($, window) {
    'use strict';

    $(document).on('wheel', 'input[type="number"]', function () {
        $(this).blur();
    });

    var GDBCommon = {
        showMessage: function ($el, text, type) {
            if (!$el || !$el.length) {
                return;
            }
            $el.text(text).removeClass('error success').addClass(type).show();

            if (!$el.find('.gdb-message-close').length) {
                var $close = $('<button type="button" class="gdb-message-close" aria-label="بستن">&times;</button>');
                $close.on('click', function () {
                    $el.hide();
                });
                $el.append($close);
            }
        },

        showResultPopup: function (data) {
            var isSuccess = !!data.success;
            var rows = data.rows || [];

            function formatNumber(value) {
                if (typeof value === 'number') {
                    return Number.isInteger(value) ? value.toLocaleString('fa-IR') : Math.round(value).toLocaleString('fa-IR');
                }
                if (typeof value === 'string' && /^-?\d+(\.\d+)?$/.test(value.trim())) {
                    var num = parseFloat(value);
                    if (Number.isInteger(num)) {
                        return num.toLocaleString('fa-IR');
                    } else {
                        return Math.round(num).toLocaleString('fa-IR');
                    }
                }
                return value;
            }

            var trackingHtml = data.tracking_code
                ? '<tr><td>کد پیگیری</td><td><strong style="direction:ltr;">' + data.tracking_code + '</strong></td></tr>'
                : '';

            var extraRowsHtml = rows.map(function (row) {
                var value = formatNumber(row.value);
                return '<tr><td>' + row.label + '</td><td><strong>' + value + '</strong></td></tr>';
            }).join('');

            var totalDisplay = data.total;
            if (totalDisplay && !isNaN(parseFloat(totalDisplay))) {
                totalDisplay = formatNumber(totalDisplay);
            }

            var iconHtml = isSuccess
                ? '<svg viewBox="0 0 52 52" class="gdb-topup-popup-check"><circle class="gdb-topup-popup-check-circle" cx="26" cy="26" r="24" fill="none"/><path class="gdb-topup-popup-check-mark" fill="none" d="M14.5 27.5l6.8 6.8 15.7-16"/></svg>'
                : '<div class="gdb-topup-popup-warning">!</div>';

            var title = data.title || (isSuccess ? 'عملیات موفق' : 'خطا');
            var message = data.message || '';

            var rowsHtml = extraRowsHtml + trackingHtml + (data.status_row || '');

            var popupHtml = ''
                + '<div class="gdb-topup-popup-overlay" role="dialog" aria-modal="true">'
                +   '<div class="gdb-topup-popup-box">'
                +     '<button type="button" class="gdb-topup-popup-close" aria-label="بستن">&times;</button>'
                +     '<div class="gdb-topup-popup-icon ' + (isSuccess ? 'gdb-topup-popup-icon-success' : 'gdb-topup-popup-icon-pending') + '">' + iconHtml + '</div>'
                +     '<h3 class="gdb-topup-popup-title">' + title + '</h3>'
                +     '<p class="gdb-topup-popup-subtitle">' + message + '</p>'
                +     (rowsHtml ? '<table class="gdb-topup-popup-table">' + rowsHtml + '</table>' : '')
                +     '<button type="button" class="gdb-topup-popup-ok">متوجه شدم</button>'
                +   '</div>'
                + '</div>';

            var $popup = $(popupHtml).appendTo('body');

            function closePopup() {
                $popup.remove();
                if (typeof data.onClose === 'function') {
                    data.onClose();
                }
            }

            $popup.on('click', '.gdb-topup-popup-close, .gdb-topup-popup-ok', closePopup);
            $popup.on('click', function (e) {
                if (e.target === this) {
                    closePopup();
                }
            });

            return $popup;
        },

        updateAllBalances: function (formattedBalance, rawDisplayBalance) {
            $('.gdb-price').each(function () {
                $(this).html(formattedBalance);
            });
            $('.gdb-withdraw-balance-value').text(formattedBalance);
            $('.gdb-wallet-balance-value').text(formattedBalance);

            if (typeof rawDisplayBalance !== 'undefined' && rawDisplayBalance !== null) {
                $('.gdb-gold-wallet-widget').data('wallet-balance', rawDisplayBalance);
            }
        },

        refreshTransactions: function () {
            var $transactionsWrapper = $('.gdb-transactions-wrapper');
            var nonce = (typeof gdb_withdraw !== 'undefined') ? gdb_withdraw.nonce : (typeof gdb !== 'undefined' ? gdb.nonce : '');
            var ajaxurl = (typeof gdb_withdraw !== 'undefined') ? gdb_withdraw.ajaxurl : (typeof gdb !== 'undefined' ? gdb.ajaxurl : '');

            if (!$transactionsWrapper.length || !ajaxurl) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'gdb_get_recent_transactions',
                    nonce: nonce,
                    limit: 10
                }
            }).done(function (response) {
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
                }
            });
        },

        refreshGoldBalances: function () {
            var ajaxurl = (typeof gdb !== 'undefined') ? gdb.ajaxurl : ((typeof gdb_withdraw !== 'undefined') ? gdb_withdraw.ajaxurl : '');
            if (!ajaxurl) {
                return;
            }

            $('[data-gdb-gold-balance-widget="1"]').each(function () {
                var $widget = $(this);
                var $content = $widget.find('.gdb-gold-balance-content');
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'gdb_get_gold_balances_html',
                        empty_text: $widget.data('empty-text') || ''
                    }
                }).done(function (response) {
                    if (response.success) {
                        $content.html(response.data.html);
                    }
                });
            });
        }
    };

    window.GDBCommon = GDBCommon;

})(jQuery, window);
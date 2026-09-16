(function($) {
    'use strict';

    $(document).ready(function() {

        $(document)
            .off('click.gdbHistory')
            .on('click.gdbHistory', '.gdb-history-button', function (e) {
                e.preventDefault();

                var $button = $(this);
                var $target = $($button.data('target'));

                if (!$target.length) {
                    return;
                }

                if ($button.data('gdb-busy')) {
                    return;
                }
                $button.data('gdb-busy', true);

                $target.stop(true, true).slideToggle(300, function () {
                    if ($target.is(':visible')) {
                        $button.text($button.data('close-text'));

                        if (window.history && window.history.pushState) {
                            var url = new URL(window.location.href);
                            url.searchParams.delete('filter_type');
                            url.searchParams.delete('filter_transaction_type');
                            url.searchParams.delete('history_page');
                            window.history.pushState({}, '', url);
                        }
                    } else {
                        $button.text($button.data('open-text'));
                    }

                    $button.data('gdb-busy', false);
                });
            });

        $('.gdb-history-button').each(function () {
            var $button = $(this);
            var $target = $($button.data('target'));

            if ($target.length && $target.is(':visible')) {
                $button.text($button.data('close-text'));
            }
        });

        if (typeof gdb === 'undefined' || !gdb.ajaxurl) {
            return;
        }

        function gdbSubmitFilter($form, extraData) {
            if ($form.data('gdb-busy')) {
                return;
            }
            $form.data('gdb-busy', true);

            var $wrap    = $form.closest('.gdb-transactions-wrapper');
            var $results = $wrap.find('.gdb-history-results').first();
            var $submit  = $form.find('.gdb-filter-submit');

            $form.addClass('gdb-filter-loading');
            $submit.prop('disabled', true);

            var postData = $form.serializeArray();

            var uniqueKeys = {};
            var filteredData = [];
            $.each(postData, function(i, field) {
                if (!uniqueKeys[field.name]) {
                    uniqueKeys[field.name] = true;
                    filteredData.push(field);
                }
            });

            filteredData.push({ name: 'action', value: 'gdb_filter_transactions' });
            filteredData.push({ name: 'nonce', value: gdb.nonce });

            if (extraData) {
                $.each(extraData, function(key, value) {
                    filteredData.push({ name: key, value: value });
                });
            }

            $.ajax({
                url: gdb.ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: $.param(filteredData)
            }).done(function (response) {
                if (response && response.success && response.data && typeof response.data.html === 'string') {
                    $results.html(response.data.html);

                    if (window.history && window.history.pushState) {
                        var url = new URL(window.location.href);
                        var ft  = response.data.filter_type || '';
                        var ftt = response.data.filter_transaction_type || '';
                        var page = response.data.page || 1;

                        if (ft) {
                            url.searchParams.set('filter_type', ft);
                        } else {
                            url.searchParams.delete('filter_type');
                        }

                        if (ftt) {
                            url.searchParams.set('filter_transaction_type', ftt);
                        } else {
                            url.searchParams.delete('filter_transaction_type');
                        }

                        if (page && page > 1) {
                            url.searchParams.set('history_page', page);
                        } else {
                            url.searchParams.delete('history_page');
                        }

                        window.history.pushState({}, '', url);
                    }

                    attachPaginationHandlers($wrap, $form);
                } else {
                    $form.off('submit.gdbHistoryFilter');
                    $form[0].submit();
                }
            }).fail(function () {
                $form.off('submit.gdbHistoryFilter');
                $form[0].submit();
            }).always(function () {
                $form.removeClass('gdb-filter-loading');
                $submit.prop('disabled', false);
                $form.data('gdb-busy', false);
            });
        }

        function attachPaginationHandlers($wrap, $form) {
            $wrap.find('.gdb-history-pagination a.page-numbers').off('click.gdbPagination').on('click.gdbPagination', function (e) {
                e.preventDefault();
                var href = $(this).attr('href');
                if (!href) return;
                var url = new URL(href, window.location.origin);
                var page = url.searchParams.get('history_page') || 1;
                gdbSubmitFilter($form, { history_page: page });
            });
        }

        $(document).on('submit.gdbHistoryFilter', 'form.gdb-history-filters[data-ajax-filter="1"]', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $wrap = $form.closest('.gdb-transactions-wrapper');

            var currentPage = 1;
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('history_page')) {
                currentPage = parseInt(urlParams.get('history_page')) || 1;
            }
            var $hiddenPage = $form.find('input[name="history_page"]');
            if ($hiddenPage.length) {
                currentPage = parseInt($hiddenPage.val()) || 1;
            }

            gdbSubmitFilter($form, { history_page: currentPage });
        });

        $('.gdb-transactions-wrapper').each(function () {
            var $wrap = $(this);
            var $form = $wrap.find('form.gdb-history-filters[data-ajax-filter="1"]');
            if ($form.length) {
                attachPaginationHandlers($wrap, $form);
            }
        });

        var gdbUrl = new URL(window.location.href);

        if (gdbUrl.searchParams.get('gdb_topup_result') === '1') {
            var gdbOrderId = gdbUrl.searchParams.get('order_id');
            var gdbOrderKey = gdbUrl.searchParams.get('key');

            if (window.history && window.history.replaceState) {
                gdbUrl.searchParams.delete('gdb_topup_result');
                gdbUrl.searchParams.delete('order_id');
                gdbUrl.searchParams.delete('key');
                window.history.replaceState({}, document.title, gdbUrl.toString());
            }

            if (gdbOrderId && gdbOrderKey) {
                $.ajax({
                    url: gdb.ajaxurl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'gdb_get_topup_order_summary',
                        nonce: gdb.nonce,
                        order_id: gdbOrderId,
                        key: gdbOrderKey
                    }
                }).done(function (response) {
                    if (response && response.success && response.data) {
                        gdbShowTopupResultPopup(response.data);
                    }
                });
            }
        }

        function gdbShowTopupResultPopup(data) {
            var isPaid = !!data.is_paid;
            var trackingCode = data.tracking_code || '';

            var iconHtml = isPaid
                ? '<svg viewBox="0 0 52 52" class="gdb-topup-popup-check"><circle class="gdb-topup-popup-check-circle" cx="26" cy="26" r="24" fill="none"/><path class="gdb-topup-popup-check-mark" fill="none" d="M14.5 27.5l6.8 6.8 15.7-16"/></svg>'
                : '<div class="gdb-topup-popup-warning">!</div>';

            var title = isPaid
                ? 'پرداخت با موفقیت انجام شد'
                : 'وضعیت سفارش شما';

            function esc(value) {
                return $('<div>').text(value == null ? '' : value).html();
            }

            var statusBadgeClasses = {
                'pending':    'gdb-status-pending',
                'processing': 'gdb-status-processing',
                'completed':  'gdb-status-completed',
                'on-hold':    'gdb-status-on-hold',
                'cancelled':  'gdb-status-cancelled',
                'refunded':   'gdb-status-refunded',
                'failed':     'gdb-status-failed',
                'rejected':   'gdb-status-rejected'
            };
            var statusBadgeClass = statusBadgeClasses[data.status_key] || 'gdb-status-pending';
            var statusHtml = '<span class="gdb-history-badge ' + statusBadgeClass + '">' + esc(data.status) + '</span>';

            var rowsHtml = ''
                + '<tr><td>شماره سفارش</td><td>' + esc(data.order_number) + '</td></tr>'
                + '<tr><td>تاریخ</td><td>' + esc(data.date) + '</td></tr>'
                + '<tr><td>مبلغ</td><td>' + esc(data.total) + '</td></tr>'
                + '<tr><td>روش پرداخت</td><td>' + esc(data.payment_method || '-') + '</td></tr>'
                + '<tr><td>وضعیت سفارش</td><td>' + statusHtml + '</td></tr>';

            if (trackingCode) {
                rowsHtml += '<tr><td>کد پیگیری</td><td><strong style="direction:ltr;">' + esc(trackingCode) + '</strong></td></tr>';
            }

            var popupHtml = ''
                + '<div class="gdb-topup-popup-overlay" role="dialog" aria-modal="true">'
                +   '<div class="gdb-topup-popup-box">'
                +     '<button type="button" class="gdb-topup-popup-close" aria-label="بستن">&times;</button>'
                +     '<div class="gdb-topup-popup-icon ' + (isPaid ? 'gdb-topup-popup-icon-success' : 'gdb-topup-popup-icon-pending') + '">' + iconHtml + '</div>'
                +     '<h3 class="gdb-topup-popup-title">' + title + '</h3>'
                +     (isPaid ? '<p class="gdb-topup-popup-subtitle">کیف پول شما با موفقیت شارژ شد.</p>' : '')
                +     '<table class="gdb-topup-popup-table">' + rowsHtml + '</table>'
                +     '<button type="button" class="gdb-topup-popup-ok">متوجه شدم</button>'
                +   '</div>'
                + '</div>';

            var $popup = $(popupHtml).appendTo('body');

            function gdbClosePopup() {
                $popup.remove();
            }

            $popup.on('click', '.gdb-topup-popup-close, .gdb-topup-popup-ok', gdbClosePopup);
            $popup.on('click', function (e) {
                if (e.target === this) {
                    gdbClosePopup();
                }
            });
        }

if (gdbUrl.searchParams.get('gdb_gold_result') === '1') {
    var gdbGoldOrderId = gdbUrl.searchParams.get('order_id');
    var gdbGoldOrderKey = gdbUrl.searchParams.get('key');

    if (window.history && window.history.replaceState) {
        gdbUrl.searchParams.delete('gdb_gold_result');
        gdbUrl.searchParams.delete('order_id');
        gdbUrl.searchParams.delete('key');
        window.history.replaceState({}, document.title, gdbUrl.toString());
    }

    if (gdbGoldOrderId && gdbGoldOrderKey) {
        $.ajax({
            url: gdb.ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'gdb_get_gold_order_summary',
                nonce: gdb.nonce,
                order_id: gdbGoldOrderId,
                key: gdbGoldOrderKey
            }
        })
        .done(function (response) {
            if (response && response.success && response.data) {
                gdbShowGoldResultPopup(response.data);
                if (typeof GDBCommon !== 'undefined') {
                    setTimeout(function() {
                        GDBCommon.refreshTransactions();
                        GDBCommon.refreshGoldBalances();
                    }, 500);
                }
            } else {
                console.error('Error in response:', response);
                var errorMsg = (response.data && response.data.message) ? response.data.message : 'خطا در دریافت اطلاعات سفارش.';
                alert(errorMsg);
            }
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            console.error('AJAX fail:', textStatus, errorThrown);
            alert('خطا در ارتباط با سرور. لطفاً صفحه را رفرش کنید.');
        });
    } else {
        console.warn('Order ID or Key missing in URL.');
    }
}

        function gdbShowGoldResultPopup(data) {
            var isPaid = !!data.is_paid;
            var trackingCode = data.tracking_code || '';

            function esc(value) {
                return $('<div>').text(value == null ? '' : value).html();
            }

            var iconHtml = isPaid
                ? '<svg viewBox="0 0 52 52" class="gdb-topup-popup-check"><circle class="gdb-topup-popup-check-circle" cx="26" cy="26" r="24" fill="none"/><path class="gdb-topup-popup-check-mark" fill="none" d="M14.5 27.5l6.8 6.8 15.7-16"/></svg>'
                : '<div class="gdb-topup-popup-warning">!</div>';

            var title = isPaid ? 'خرید طلا با موفقیت انجام شد' : 'وضعیت سفارش شما';

            var statusBadgeClasses = {
                'pending': 'gdb-status-pending', 'processing': 'gdb-status-processing',
                'completed': 'gdb-status-completed', 'on-hold': 'gdb-status-on-hold',
                'cancelled': 'gdb-status-cancelled', 'refunded': 'gdb-status-refunded', 'failed': 'gdb-status-failed'
            };
            var statusHtml = '<span class="gdb-history-badge ' + (statusBadgeClasses[data.status_key] || 'gdb-status-pending') + '">' + esc(data.status) + '</span>';

            var rowsHtml = ''
                + '<tr><td>شماره سفارش</td><td>' + esc(data.order_number) + '</td></tr>'
                + '<tr><td>مقدار خریداری‌شده</td><td>' + esc(data.quantity) + ' ' + esc(data.unit_label) + '</td></tr>';

            if (data.wallet_portion_formatted) {
                rowsHtml += '<tr><td>پرداخت‌شده از کیف پول</td><td><strong>' + esc(data.wallet_portion_formatted) + '</strong></td></tr>';
                rowsHtml += '<tr><td>پرداخت‌شده از درگاه</td><td><strong>' + data.gateway_portion_formatted + '</strong></td></tr>';
            } else {
                rowsHtml += '<tr><td>مبلغ کل</td><td>' + data.gateway_portion_formatted + '</td></tr>';
            }

            rowsHtml += '<tr><td>تاریخ</td><td>' + esc(data.date) + '</td></tr>'
                + '<tr><td>وضعیت سفارش</td><td>' + statusHtml + '</td></tr>';

            if (trackingCode) {
                rowsHtml += '<tr><td>کد پیگیری</td><td><strong style="direction:ltr;">' + esc(trackingCode) + '</strong></td></tr>';
            }

            var popupHtml = ''
                + '<div class="gdb-topup-popup-overlay" role="dialog" aria-modal="true">'
                +   '<div class="gdb-topup-popup-box">'
                +     '<button type="button" class="gdb-topup-popup-close" aria-label="بستن">&times;</button>'
                +     '<div class="gdb-topup-popup-icon ' + (isPaid ? 'gdb-topup-popup-icon-success' : 'gdb-topup-popup-icon-pending') + '">' + iconHtml + '</div>'
                +     '<h3 class="gdb-topup-popup-title">' + title + '</h3>'
                +     '<table class="gdb-topup-popup-table">' + rowsHtml + '</table>'
                +     '<button type="button" class="gdb-topup-popup-ok">متوجه شدم</button>'
                +   '</div>'
                + '</div>';

            var $popup = $(popupHtml).appendTo('body');

            function gdbCloseGoldPopup() {
                $popup.remove();
            }

            $popup.on('click', '.gdb-topup-popup-close, .gdb-topup-popup-ok', gdbCloseGoldPopup);
            $popup.on('click', function (e) {
                if (e.target === this) {
                    gdbCloseGoldPopup();
                }
            });
        }

    });

})(jQuery);
(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('GDB Admin loaded.');

        $(document).on('wheel', 'input[type="number"]', function () {
            $(this).blur();
        });

        if (typeof gdbAdminVars !== 'undefined' && gdbAdminVars.parsidateActive) {
        } else if ($.fn.datepicker) {
            $('.date-picker').each(function() {
                if (!$(this).hasClass('hasDatepicker')) {
                    $(this).datepicker({
                        dateFormat: 'yy-mm-dd',
                        changeMonth: true,
                        changeYear: true
                    });
                }
            });
        }

        var urlParams = new URLSearchParams(window.location.search);
        var message = urlParams.get('message');
        var gdbError = urlParams.get('gdb_error');

        if (gdbError) {
            var $errorNotice = $('<div class="notice notice-error is-dismissible"><p>' + $('<div>').text(gdbError).html() + '</p></div>');
            $('.wrap h1:first').after($errorNotice);
            if (window.history && window.history.replaceState) {
                urlParams.delete('gdb_error');
                var newUrlErr = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
                window.history.replaceState({}, '', newUrlErr);
            }
        }

        if (message) {
            var noticeClass = 'notice-success';
            var noticeText = '';
            switch(message) {
                case 'approved':
                    noticeText = 'درخواست با موفقیت تایید شد.';
                    break;
                case 'rejected':
                    noticeText = 'درخواست با موفقیت رد شد.';
                    break;
                case 'saved':
                    noticeText = 'تنظیمات با موفقیت ذخیره شد.';
                    break;
                case 'updated':
                    noticeText = 'اطلاعات با موفقیت به‌روزرسانی شد.';
                    break;
                default:
                    noticeClass = 'notice-info';
                    noticeText = 'عملیات با موفقیت انجام شد.';
            }
            if (noticeText) {
                var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + noticeText + '</p></div>');
                $('.wrap h1:first').after($notice);
                if (window.history && window.history.replaceState) {
                    urlParams.delete('message');
                    var newUrl = window.location.pathname + '?' + urlParams.toString();
                    window.history.replaceState({}, '', newUrl);
                }
            }
        }
    });

})(jQuery);
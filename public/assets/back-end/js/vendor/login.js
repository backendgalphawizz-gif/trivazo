"use strict";

$(document).on('ready', function () {
    $('.js-toggle-password').each(function () {
        new HSTogglePassword(this).init();
    });
    $('.js-validate').each(function () {
        $.HSCore.components.HSValidation.init($(this));
    });
});

$('.clear-alter-message').on('click', function () {
    $('.vendor-suspend').addClass('d-none');
});

$('#copyLoginInfo').on('click', function () {
    let vendorEmail = $('#vendor-email').data('email');
    let vendorPassword = $('#vendor-password').data('password');
    $('#signingVendorEmail').val(vendorEmail);
    $('#signingVendorPassword').val(vendorPassword);
    toastr.success($('#message-copied_success').data('text'), 'Success!', {
        CloseButton: true,
        ProgressBar: true
    });
});

$('.onerror-logo').on('error', function () {
    let image = $('#onerror-logo').data('onerror-logo');
    $(this).attr('src', image);
});

humhub.module('jitsiMeet.config', function (module, require, $) {
    var action = require('action');
    var status = require('ui.status');

    var CUSTOM_DOMAIN = '__custom__';

    var displayJwtParams = function () {
        if ($('#settingsform-enablejwt').is(':checked')) {
            $('.field-settingsform-jitsiappid').show();
            $('.field-settingsform-jitsiappsecret').show();
        } else {
            $('.field-settingsform-jitsiappid').hide();
            $('.field-settingsform-jitsiappsecret').hide();
        }
    };

    var displayJaas = function () {
        var isJaas = $('#settingsform-mode').val() === 'jaas';
        var jaasFields = [
            '.field-settingsform-jaasappid',
            '.field-settingsform-jaaskid',
            '.field-settingsform-jaasprivatekeypath',
            '.field-settingsform-jaaswebhooksecret',
            '.field-settingsform-jaaswebhookdrifttolerance',
            '.field-settingsform-jaasdomain',
            '.field-settingsform-jaasenablerecording',
            '.field-settingsform-jaasenablelivestreaming',
            '.field-settingsform-jaasenablemoderation'
        ];

        jaasFields.forEach(function (selector) {
            if (isJaas) {
                $(selector).show();
            } else {
                $(selector).hide();
            }
        });
    };

    var toggleJitsiDomainTextInput = function () {
        var customWrap = $('.field-settingsform-jitsidomain-custom');
        if ($('#settingsform-jitsidomain').val() === CUSTOM_DOMAIN) {
            customWrap.removeClass('hide').show();
        } else {
            customWrap.addClass('hide').hide();
        }
    };

    var prepareJitsiDomainSubmit = function () {
        var preset = $('#settingsform-jitsidomain').val();
        var value = preset === CUSTOM_DOMAIN
            ? $('#settingsform-jitsidomain-custom').val()
            : preset;
        $('#settingsform-jitsidomain-value').val(value);
    };

    var registerCopyWebhookHandler = function () {
        action.registerHandler('jitsiCopyWebhookUrl', function (evt) {
            var url = $('#jitsi-webhook-url').val();
            clipboard.writeText(url).then(function () {
                status.success(module.text('webhookUrlCopied'));
            }).catch(function () {
                status.error(module.text('webhookUrlCopyFailed'), true);
            });
            evt.finish();
        });
    };

    var init = function () {
        displayJwtParams();
        displayJaas();
        toggleJitsiDomainTextInput();
        registerCopyWebhookHandler();

        $(document.body).on('change', '#settingsform-enablejwt', displayJwtParams);
        $(document.body).on('change', '#settingsform-mode', displayJaas);
        $(document.body).on('change', '#settingsform-jitsidomain', toggleJitsiDomainTextInput);
        $('#configure-form').on('submit', prepareJitsiDomainSubmit);
    };

    module.export({
        init: init
    });

    init();
});

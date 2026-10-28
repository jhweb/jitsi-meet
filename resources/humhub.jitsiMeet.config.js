humhub.module('jitsiMeet.config', function (module, require, $) {
    var action = require('action');
    var status = require('ui.status');

    var CUSTOM_DOMAIN = '__custom__';

    var showElement = function ($el) {
        $el.removeClass('hide').show();
    };

    var hideElement = function ($el) {
        $el.addClass('hide').hide();
    };

    var displayJwtParams = function () {
        if ($('#settingsform-mode').val() === 'jaas') {
            hideElement($('.field-settingsform-jitsiappid'));
            hideElement($('.field-settingsform-jitsiappsecret'));
            return;
        }
        if ($('#settingsform-enablejwt').is(':checked')) {
            showElement($('.field-settingsform-jitsiappid'));
            showElement($('.field-settingsform-jitsiappsecret'));
        } else {
            hideElement($('.field-settingsform-jitsiappid'));
            hideElement($('.field-settingsform-jitsiappsecret'));
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
            '.field-settingsform-jaasenablemoderation',
            '.jitsi-debug-section'
        ];
        var selfHostedFields = [
            '.field-settingsform-jitsidomain',
            '.field-settingsform-enablejwt'
        ];

        jaasFields.forEach(function (selector) {
            if (isJaas) {
                showElement($(selector));
            } else {
                hideElement($(selector));
            }
        });

        selfHostedFields.forEach(function (selector) {
            if (isJaas) {
                hideElement($(selector));
            } else {
                showElement($(selector));
            }
        });

        if (isJaas) {
            hideElement($('.field-settingsform-jitsiappid'));
            hideElement($('.field-settingsform-jitsiappsecret'));
        } else {
            displayJwtParams();
        }
    };

    var prepareJitsiDomainSubmit = function () {
        var preset = $('#settingsform-jitsidomain').val();
        var value = preset === CUSTOM_DOMAIN
            ? $('#settingsform-jitsidomain-custom').val()
            : preset;
        $('#settingsform-jitsidomain-value').val(value);
    };

    var toggleJitsiDomainTextInput = function () {
        var customWrap = $('.field-settingsform-jitsidomain-custom');
        if ($('#settingsform-jitsidomain').val() === CUSTOM_DOMAIN) {
            showElement(customWrap);
        } else {
            hideElement(customWrap);
        }
        prepareJitsiDomainSubmit();
    };

    var registerCopyWebhookHandler = function () {
        action.registerHandler('jitsiCopyWebhookUrl', function (evt) {
            var url = $('#jitsi-webhook-url').val();
            var cb = (window.clipboard && window.clipboard.writeText) ? window.clipboard : navigator.clipboard;
            cb.writeText(url).then(function () {
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
        prepareJitsiDomainSubmit();
        registerCopyWebhookHandler();

        $(document.body).on('change', '#settingsform-enablejwt', displayJwtParams);
        $(document.body).on('change', '#settingsform-mode', displayJaas);
        $(document.body).on('change', '#settingsform-jitsidomain', toggleJitsiDomainTextInput);
        $(document.body).on('input change', '#settingsform-jitsidomain-custom', prepareJitsiDomainSubmit);
        $('#configure-form').on('submit', prepareJitsiDomainSubmit);
    };

    module.export({
        init: init
    });

    init();
});

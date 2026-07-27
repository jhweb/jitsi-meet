humhub.module('jitsiMeet.config', function (module, require, $) {

    var jwtFieldSelectors = [
        '.field-settingsform-jitsiappid',
        '.field-settingsform-jitsiappsecret'
    ];

    var jaasFieldSelectors = [
        '.field-settingsform-jaasappid',
        '.field-settingsform-jaaskid',
        '.field-settingsform-jaasprivatekeypath',
        '.field-settingsform-jaaswebhooksecret',
        '.field-settingsform-jaaswebhookdrifttolerance',
        '.field-settingsform-jaasdomain',
        '.field-settingsform-jaasenablerecording',
        '.field-settingsform-jaasenablelivestreaming',
        '.field-settingsform-jaasenablemoderation',
        '.jitsi-webhook-url-group'
    ];

    var displayJwtParams = function () {
        var show = $('#settingsform-enablejwt').is(':checked');
        jwtFieldSelectors.forEach(function (selector) {
            $(selector).toggle(show);
        });
    };

    var displayJaas = function () {
        var show = $('#settingsform-mode').val() === 'jaas';
        jaasFieldSelectors.forEach(function (selector) {
            $(selector).toggle(show);
        });
    };

    var toggleJitsiDomainTextInput = function () {
        var dropdown = $('#settingsform-jitsidomain');
        var textInput = $('.field-settingsform-jitsidomain-custom');
        textInput.children('.control-label').detach();

        if (dropdown.val() === '') {
            textInput.show();
        } else {
            textInput.hide();
        }
    };

    var disableInactiveDomainField = function () {
        var dropdown = $('#settingsform-jitsidomain');
        var textInputField = $('#settingsform-jitsidomain-custom');

        if (dropdown.val() === '') {
            dropdown.prop('disabled', true);
        } else {
            textInputField.prop('disabled', true);
        }
    };

    var copyWebhookUrl = function () {
        var url = $('#jitsi-webhook-url').val();
        clipboard.writeText(url).then(function () {
            require('ui.status').success(module.text('copied'));
        }).catch(function () {
            require('ui.status').error(module.text('copyError'), true);
        });
    };

    var init = function () {
        displayJwtParams();
        displayJaas();
        toggleJitsiDomainTextInput();

        $(document.body).on('change', '#settingsform-enablejwt', displayJwtParams);
        $(document.body).on('change', '#settingsform-mode', displayJaas);
        $(document.body).on('change', '#settingsform-jitsidomain', toggleJitsiDomainTextInput);
        $('#configure-form').on('submit', disableInactiveDomainField);
        $('#jitsi-webhook-url-copy').on('click', copyWebhookUrl);
    };

    module.export({
        init: init
    });

    $(function () {
        module.init();
    });
});

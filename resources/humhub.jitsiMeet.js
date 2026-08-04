humhub.module('jitsiMeet', function (module, require, $) {
    var modal = require('ui.modal');
    var object = require('util').object;
    var Widget = require('ui.widget').Widget;
    var log = require('log');
    var status = require('ui.status');

    var Room = function (node, options) {
        Widget.call(this, node, options);
    };

    object.inherits(Room, Widget);

    Room.prototype.getDefaultOptions = function () {
        return {
            'roomName': 'unnamed',
            'jwt': '',
        };
    };

    Room.prototype.init = function () {
        var that = this;

        this.initJitsi();

        this.modal = modal.get('jitsiMeet-modal');
        this.modal.$.on('hidden.bs.modal', function () {
            that.modal.clear();
        });
    };

    Room.prototype.close = function (evt) {
        this.modal.clear();
        this.modal.close();
        evt.finish();

        this.jitsiApi.executeCommand('hangup');
    };

    Room.prototype._showContainerError = function (container, headingText, bodyText) {
        if (!container) {
            return;
        }

        container.textContent = '';

        var wrapper = document.createElement('div');
        wrapper.style.cssText = 'padding: 20px; text-align: center; color: var(--danger, #a94442);';

        var heading = document.createElement('h3');
        heading.textContent = headingText;
        wrapper.appendChild(heading);

        if (bodyText) {
            var paragraph = document.createElement('p');
            paragraph.textContent = bodyText;
            wrapper.appendChild(paragraph);
        }

        container.appendChild(wrapper);
    };

    Room.prototype.initJitsi = function () {
        var that = this;
        var mode = this.options.mode || 'self_hosted';
        var domain = this.options.jitsidomain;

        var scriptUrl = 'https://' + domain + '/external_api.js';
        if (mode === 'jaas') {
            var jaasDomain = this.options.jaasdomain || '8x8.vc';
            scriptUrl = 'https://' + jaasDomain + '/libs/external_api.min.js';
        }

        var startMeeting = function () {
            that._startMeeting();
        };

        if (typeof window.JitsiMeetExternalAPI === 'undefined') {
            $.ajax({
                url: scriptUrl,
                dataType: 'script',
                cache: true
            }).done(function () {
                startMeeting();
            }).fail(function (jqxhr, settings, exception) {
                log.error('Failed to load JitsiMeet API: ' + exception);
                that._showContainerError(
                    document.querySelector('#jitsiMeetD'),
                    'Error loading conferencing API',
                    'Please refresh or try again later.'
                );
                status.error('Failed to load video conferencing API.');
            });
        } else {
            startMeeting();
        }
    };

    Room.prototype._startMeeting = function () {
        var that = this;
        var mode = this.options.mode || 'self_hosted';
        var domain = this.options.jitsidomain;
        var roomName = this.options.roomname;
        var jwt = this.options.jwt;

        if (typeof this.options.roomprefix === 'string' && this.options.roomprefix !== '') {
            roomName = this.options.roomprefix + this.options.roomname;
        }

        if (mode === 'jaas') {
            domain = this.options.jaasdomain || '8x8.vc';
            var appId = this.options.jaasappid;
            if (appId) {
                roomName = appId + '/' + this.options.roomname;
            }
        }

        var baseUrl = window.location.protocol + '//' + window.location.host;
        var inviteDomain = window.location.host;
        var originalRoomName = this.options.roomname;
        var conferenceUrl = baseUrl + '/conference/' + originalRoomName;
        var inviteServiceUrl = baseUrl + '/jitsi-meet-cloud-8x8/room/invite';

        var startSilent = this.options.startSilent === true ||
            (typeof this.options.startSilent === 'string' && this.options.startSilent === 'true') ||
            window.location.hash.indexOf('config.startSilent=true') !== -1;

        var options = {
            roomName: roomName,
            parentNode: document.querySelector('#jitsiMeetD'),
            height: window.innerHeight - 160,
            jwt: jwt,
            nossl: jwt === '',
            interfaceConfigOverwrite: {
                RECENT_LIST_ENABLED: false,
                GENERATE_ROOMNAMES_ON_WELCOME_PAGE: false,
                DISPLAY_WELCOME_PAGE_CONTENT: false,
            },
            userInfo: {
                fullName: this.options.userdisplayname,
                displayName: this.options.userdisplayname,
                avatarUrl: this.options.useravatar
            },
            configOverwrite: {
                disableDeepLinking: true,
                inviteDomain: inviteDomain,
                inviteServiceUrl: inviteServiceUrl,
                brandingRoomAlias: 'conference/' + originalRoomName,
                deploymentInfo: {
                    shard: 'shard1',
                    region: 'us',
                    userRegion: 'us',
                    appId: mode === 'jaas' ? this.options.jaasappid : undefined
                },
                startSilent: startSilent,
                startAudioMuted: startSilent,
                startVideoMuted: false,
            }
        };

        try {
            this.jitsiApi = new JitsiMeetExternalAPI(domain, options);

            if (this.jitsiApi && typeof this.jitsiApi.getRoomURL === 'function') {
                this.jitsiApi.getRoomURL = function () {
                    return conferenceUrl;
                };
            }

            this.jitsiApi.addEventListeners({
                readyToClose: function () {
                    that.close();
                },
                videoConferenceJoined: function () {
                    setTimeout(function () {
                        if (that.jitsiApi && typeof that.jitsiApi.getRoomURL === 'function') {
                            that.jitsiApi.getRoomURL = function () {
                                return conferenceUrl;
                            };
                        }
                    }, 2000);
                },
                error: function (error) {
                    log.error('JitsiMeet API error: ' + (error && error.message ? error.message : String(error)));
                    status.error('Video conference error. Please try again.');
                }
            });
        } catch (error) {
            log.error('Failed to initialize JitsiMeet API: ' + (error && error.message ? error.message : String(error)));
            this._showContainerError(
                document.querySelector('#jitsiMeetD'),
                'Failed to load video conference',
                'Please check your configuration and try again.'
            );
            status.error('Failed to load video conference.');
        }
    };

    module.export({
        Room: Room,
    });

});

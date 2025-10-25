humhub.module('jitsi-meet-cloud-8x8', function (module, require, $) {
    var modal = require('ui.modal');
    var object = require('util').object;
    var Widget = require('ui.widget').Widget;
    var client = require('client');
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
        this.modal.$.on('hidden.bs.modal', function (evt) {
            that.modal.clear();
        });
    };

    Room.prototype.close = function (evt) {
        this.modal.clear();
        this.modal.close();
        evt.finish();

        this.jitsiApi.executeCommand('hangup');

    }

    Room.prototype.initJitsi = function () {
        var that = this;

        var mode = this.options.mode || 'self_hosted';
        var domain = this.options.jitsidomain;
        var roomName = this.options.roomname;

        // Enhanced console logging for debugging
        console.log('JitsiMeet Room Widget - Initializing Jitsi');
        console.log('Mode:', mode);
        console.log('Original domain:', domain);
        console.log('Original roomName:', roomName);
        console.log('JWT present:', !!this.options.jwt);

        if (typeof this.options.roomprefix === 'string' && this.options.roomprefix !== '') {
            roomName = this.options.roomprefix + this.options.roomname;
            console.log('Room name with prefix:', roomName);
        }

        if (mode === 'jaas') {
            // Override domain and roomName per JaaS
            domain = this.options.jaasdomain || '8x8.vc';
            var appId = this.options.jaasappid;
            if (appId) {
                roomName = appId + '/' + this.options.roomname;
                console.log('JaaS room name:', roomName);
            }
            console.log('JaaS domain:', domain);
        }

        jwt = this.options.jwt;

        const options = {
            roomName: roomName,
            parentNode: document.querySelector('#jitsiMeetD'),
            //Todo: Fixme
            height: window.innerHeight - 160,
            jwt: jwt,
            nossl: jwt == '',
            interfaceConfigOverwrite: {
                RECENT_LIST_ENABLED: false,
                GENERATE_ROOMNAMES_ON_WELCOME_PAGE: false,
                DISPLAY_WELCOME_PAGE_CONTENT: false,
                //filmStripOnly: true,
            },
            userInfo: {
                fullName: this.options.userdisplayname,
                displayName: this.options.userdisplayname,
            },
            configOverwrite: {
                // Workaround for broken "open in app" link on Android
                disableDeepLinking: true,
            }
        };

        console.log('JitsiMeet API Options:', options);

        try {
            this.jitsiApi = new JitsiMeetExternalAPI(domain, options);
            console.log('JitsiMeet API initialized successfully');
            
            this.jitsiApi.addEventListeners({
                readyToClose: function () {
                    console.log('JitsiMeet API - readyToClose event');
                    that.close();
                },
                videoConferenceJoined: function () {
                    console.log('JitsiMeet API - videoConferenceJoined event');
                },
                videoConferenceLeft: function () {
                    console.log('JitsiMeet API - videoConferenceLeft event');
                },
                error: function (error) {
                    console.error('JitsiMeet API Error:', error);
                }
            });
        } catch (error) {
            console.error('Failed to initialize JitsiMeet API:', error);
            // Display user-friendly error message
            document.querySelector('#jitsiMeetD').innerHTML = 
                '<div style="padding: 20px; text-align: center; color: red;">' +
                '<h3>Failed to load video conference</h3>' +
                '<p>Please check your configuration and try again.</p>' +
                '<p>Error: ' + error.message + '</p>' +
                '</div>';
        }
    }

    // Space namespace for space-related actions
    var Space = function() {};
    
    Space.prototype.quickPostModal = function(evt) {
        modal.load(evt.$trigger.data('action-url'), function(html) {
            modal.set(html);
        });
    };

    Space.prototype.quickPost = function(evt) {
        var $form = evt.$form || evt.$trigger.closest('form');
        var title = $form.find('#video-chat-title').val();
        var description = $form.find('#video-chat-description').val();
        
        client.post(evt.$trigger.data('action-url'), {
            data: {
                title: title,
                description: description
            },
            success: function(response) {
                if (response.success) {
                    modal.close();
                    status.success(response.message);
                    if (response.joinUrl) {
                        window.location.href = response.joinUrl;
                    }
                } else {
                    status.error(response.error || 'Failed to start video chat');
                }
            },
            error: function() {
                status.error('Failed to start video chat');
            }
        });
    };

    // Form submission handler for instant video chat creation
    var Form = function() {};
    
    Form.prototype.submit = function(evt) {
        var $form = evt.$form || evt.$trigger.closest('form');
        var roomName = $form.find('input[name*="room_name"]').val();
        var title = $form.find('input[name*="title"]').val();
        var description = $form.find('textarea[name*="description"]').val();
        
        client.post(evt.$trigger.data('action-url'), {
            data: {
                'InstantVideoChat[room_name]': roomName,
                'InstantVideoChat[title]': title,
                'InstantVideoChat[description]': description
            },
            success: function(response) {
                if (response.success) {
                    status.success(response.message || 'Video chat created successfully');
                    // Reload the page to show the new content
                    window.location.reload();
                } else {
                    status.error(response.error || 'Failed to create video chat');
                }
            },
            error: function() {
                status.error('Failed to create video chat');
            }
        });
    };
    
    // Auto-bind form submission for jitsi-meet-cloud-8x8 forms
    $(document).on('click', '[data-content-component="jitsi-meet-cloud-8x8"] button[type="submit"]', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $form = $button.closest('form');
        var $container = $form.closest('[data-content-component="jitsi-meet-cloud-8x8"]');
        
        // Set up the action attributes for the form handler
        $button.attr('data-action-click', 'jitsi-meet-cloud-8x8.Form.submit');
        $button.attr('data-action-url', $container.data('action-url') || '/jitsi-meet-cloud-8x8/instant-video-chat/create');
        
        // Trigger the form submission
        $button.trigger('click');
    });

    module.export({
        Room: Room,
        Space: Space,
        Form: Form,
    });

});

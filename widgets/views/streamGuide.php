<?php

use humhub\libs\Html;
use yii\helpers\Url;
use humhub\modules\tour\assets\TourAsset;

/* @var $forceStart bool */

// Register the Tour module assets
TourAsset::register($this);

?>
<!-- Jitsi StreamGuide Widget (Universal: Bootstrap Tour + Driver.js) -->
<script <?= Html::nonce() ?>>
(function($){
    $(document).one('humhub:ready', function () {
        var log = humhub.require('log');
        
        var tourId = 'jitsi-stream-guide';
        var storageKey = 'humhub.jitsi.tour.seen';
        var forceStartUrl = <?= $forceStart ? 'true' : 'false' ?>;

        var startStreamTour = function (force) {
            log.debug('[JitsiGuide] startStreamTour called. Force: ' + force);
            
            if (!force && !forceStartUrl && localStorage.getItem(storageKey)) {
                log.debug('[JitsiGuide] Tour already seen, skipping.');
                return;
            }

            try {
                var tourModule = humhub.require('tour');
                
                if (!tourModule || typeof tourModule.start !== 'function') {
                    log.error('[JitsiGuide] Tour module not available.');
                    return;
                }

                // Patch Dashboard URL for both versions to prevent redirect
                if (tourModule.config) {
                    tourModule.config.dashboardUrl = window.location.href; // Stay on page
                    tourModule.config.completedUrl = '#'; // Prevent server 500
                    log.debug('[JitsiGuide] Patched config (dashboardUrl, completedUrl).');
                }

                // Steps Data (Abstracted)
                var stepsData = [
                    {
                        sel: false, // Orphan
                        title: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Welcome to Live Stream')) ?>,
                        desc: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Here you can join active meetings, start your own, or watch past recordings.')) ?>
                    },
                    {
                        sel: '#jitsi-join-panel',
                        title: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Join or Create a Room')) ?>,
                        desc: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Enter a room name here to start a new meeting or join an existing one. You can also choose to open it in a new window.')) ?>,
                        pos: 'bottom'
                    },
                    {
                        sel: '#jitsi-active-grid',
                        title: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Active Streams')) ?>,
                        desc: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Currently active live streams will appear here. Click "JOIN LIVE STREAM" to watch or participate.')) ?>,
                        pos: 'top'
                    },
                    {
                        sel: '#jitsi-ended-grid',
                        title: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Ended Streams')) ?>,
                        desc: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Past streams are archived here. You can see details like duration and participant counts.')) ?>,
                        pos: 'top'
                    },
                    {
                        sel: '#jitsi-guide-button',
                        title: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'You are ready!')) ?>,
                        desc: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'You can restart this guide anytime by clicking the "Guide" button.')) ?>,
                        pos: 'bottom'
                    }
                ];

                // FEATURE DETECTION
                var isLegacyBootstrap = (typeof Tour !== 'undefined');
                
                if (isLegacyBootstrap) {
                    // --- HUMHUB 1.17.x / BOOTSTRAP TOUR ---
                    log.debug('[JitsiGuide] Detected Bootstrap Tour (Legacy).');
                    
                    var legacySteps = stepsData.map(function(s) {
                        var step = {
                            title: s.title,
                            content: s.desc,
                            orphan: !s.sel,
                            backdrop: true
                        };
                        if (s.sel) {
                            step.element = s.sel;
                            step.placement = s.pos || 'auto';
                        }
                        return step;
                    });

                    tourModule.start({
                        name: tourId,
                        steps: legacySteps,
                        template: tourModule.config.template // Pass template if available
                    });

                } else {
                    // --- HUMHUB 1.18+ / DRIVER.JS ---
                    log.debug('[JitsiGuide] Detected Driver.js (New).');
                    
                    var driverSteps = stepsData.map(function(s) {
                        return {
                            element: s.sel ? s.sel : undefined,
                            popover: {
                                title: s.title,
                                description: s.desc,
                                side: s.pos || 'bottom'
                            }
                        };
                    });

                    tourModule.start({
                        tourId: tourId,
                        nextUrl: '', 
                        driverJs: {
                            showProgress: true,
                            showButtons: ['next', 'previous', 'close'],
                            steps: driverSteps
                        }
                    });
                }
                
                // Mark seen
                localStorage.setItem(storageKey, 'true');
                log.debug('[JitsiGuide] Tour started successfully.');

            } catch (e) {
                log.error('[JitsiGuide] Error starting tour: ' + e);
            }
        };

        // Auto-run if forced
        if (forceStartUrl) {
            startStreamTour(true);
        } else {
            startStreamTour(false);
        }

        // Button click handler
        $(document).off('click', '#jitsi-guide-button').on('click', '#jitsi-guide-button', function(e) {
            e.preventDefault();
            log.debug('[JitsiGuide] Button Clicked');
            startStreamTour(true);
        });

    });
})(jQuery);
</script>

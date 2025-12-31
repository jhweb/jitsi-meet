<?php

use humhub\libs\Html;
use yii\helpers\Url;
use humhub\modules\tour\assets\TourAsset;

/* @var $forceStart bool */

// Register the Tour module assets
TourAsset::register($this);

?>
<!-- Jitsi StreamGuide Widget (Driver.js API for HumHub 1.17+) -->
<script <?= Html::nonce() ?>>
(function($){
    console.log('[JitsiGuide] Script Loaded');

    $(document).one('humhub:ready', function () {
        console.log('[JitsiGuide] HumHub Ready');
        
        var tourId = 'jitsi-stream-guide';
        var storageKey = 'humhub.jitsi.tour.seen';
        var forceStartUrl = <?= $forceStart ? 'true' : 'false' ?>;

        var startStreamTour = function (force) {
            console.log('[JitsiGuide] startStreamTour called. Force:', force);
            
            if (!force && !forceStartUrl && localStorage.getItem(storageKey)) {
                console.log('[JitsiGuide] Tour already seen, skipping.');
                return;
            }

            try {
                var tourModule = humhub.require('tour');
                
                if (!tourModule || typeof tourModule.start !== 'function') {
                    console.error('[JitsiGuide] Tour module not available.');
                    return;
                }
                
                console.log('[JitsiGuide] Starting tour with Driver.js API...');

                // CRITICAL: Prevent redirect to dashboard after tour ends
                // The HumHub wrapper redirects to dashboardUrl when tour completes
                // We set it to current page URL to stay on this page
                if (tourModule.config) {
                    tourModule.config.dashboardUrl = window.location.href;
                    console.log('[JitsiGuide] Patched dashboardUrl to stay on current page');
                }

                // HumHub 1.17+ (beta) Driver.js API format
                tourModule.start({
                    tourId: tourId,
                    nextUrl: '', // Empty to prevent redirect after tour
                    driverJs: {
                        showProgress: true,
                        showButtons: ['next', 'previous', 'close'],
                        steps: [
                            {
                                popover: {
                                    title: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Welcome to Live Stream')) ?>,
                                    description: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Here you can join active meetings, start your own, or watch past recordings.')) ?>
                                }
                            },
                            {
                                element: '#jitsi-join-panel',
                                popover: {
                                    title: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Join or Create a Room')) ?>,
                                    description: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Enter a room name here to start a new meeting or join an existing one. You can also choose to open it in a new window.')) ?>,
                                    side: 'bottom'
                                }
                            },
                            {
                                element: '#jitsi-active-grid',
                                popover: {
                                    title: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Active Streams')) ?>,
                                    description: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Currently active live streams will appear here. Click "JOIN LIVE STREAM" to watch or participate.')) ?>,
                                    side: 'top'
                                }
                            },
                            {
                                element: '#jitsi-ended-grid',
                                popover: {
                                    title: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Ended Streams')) ?>,
                                    description: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Past streams are archived here. You can see details like duration and participant counts.')) ?>,
                                    side: 'top'
                                }
                            },
                            {
                                element: '#jitsi-guide-button',
                                popover: {
                                    title: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'You are ready!')) ?>,
                                    description: <?= json_encode(Yii::t('JitsiMeetCloud8x8Module.base', 'You can restart this guide anytime by clicking the "Guide" button.')) ?>,
                                    side: 'bottom'
                                }
                            }
                        ]
                    }
                });

                // Mark as seen after starting (Driver.js will handle visual flow)
                localStorage.setItem(storageKey, 'true');
                console.log('[JitsiGuide] Tour started successfully.');

            } catch (e) {
                console.error('[JitsiGuide] Error starting tour:', e);
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
            console.log('[JitsiGuide] Button Clicked');
            startStreamTour(true);
        });

    });
})(jQuery);
</script>

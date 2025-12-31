<?php

use humhub\libs\Html;
use yii\helpers\Url;

?>
<script <?= Html::nonce() ?>>
    $(document).one('humhub:ready', function () {
        var tourId = 'jitsi-stream-guide';
        var storageKey = 'humhub.jitsi.tour.seen';

        // Function to start the tour
        var startStreamTour = function (force = false) {
            // Check if already seen
            if (!force && localStorage.getItem(storageKey)) {
                return;
            }

            humhub.require('tour').start({
                name: tourId,
                steps: [
                    {
                        orphan: true,
                        backdrop: true,
                        title: "<?= Yii::t('JitsiMeetCloud8x8Module.base', '<strong>Live Stream</strong>') ?>",
                        content: "<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Welcome to the Live Stream section!<br><br>Here you can join active meetings, start your own, or watch past recordings.') ?>"
                    },
                    {
                        element: "#jitsi-join-panel",
                        title: "<?= Yii::t('JitsiMeetCloud8x8Module.base', '<strong>Join</strong> or Create') ?>",
                        content: "<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Enter a room name here to start a new meeting or join an existing one.<br><br>You can also choose to open it in a new window.') ?>",
                        placement: "bottom"
                    },
                    {
                        element: "#jitsi-active-grid",
                        title: "<?= Yii::t('JitsiMeetCloud8x8Module.base', '<strong>Active</strong> Streams') ?>",
                        content: "<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Currently active live streams will appear here.<br><br>Click \"JOIN LIVE STREAM\" to watch or participate.') ?>",
                        placement: "top"
                    },
                    {
                        element: "#jitsi-ended-grid",
                        title: "<?= Yii::t('JitsiMeetCloud8x8Module.base', '<strong>Ended</strong> Streams') ?>",
                        content: "<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Past streams are archived here. You can see details like duration and participant counts.') ?>",
                        placement: "top"
                    },
                    {
                        element: ".btn-stream-replay:visible:first", 
                        title: "<?= Yii::t('JitsiMeetCloud8x8Module.base', '<strong>Downloads</strong> & Replays') ?>",
                        content: "<?= Yii::t('JitsiMeetCloud8x8Module.base', 'If recordings or files are available, you will see a \"DOWNLOAD FILES\" button here.<br><br>Click it to access video recordings, chat logs, and transcripts.') ?>",
                        placement: "left",
                        onShow: function(tour) {
                            // Fallback if no download button is currently visible
                            if($('.btn-stream-replay:visible').length === 0) {
                                tour._options.steps[tour._current].orphan = true;
                                tour._options.steps[tour._current].element = null;
                            }
                        }
                    },
                    {
                        orphan: true,
                        backdrop: true,
                        title: "<?= Yii::t('JitsiMeetCloud8x8Module.base', '<strong>Ready</strong> to go!') ?>",
                        content: "<?= Yii::t('JitsiMeetCloud8x8Module.base', 'You are now ready to use the Live Stream feature.<br><br>You can restart this guide anytime by clicking the \"Guide\" button.') ?>",
                        onEnd: function() {
                            localStorage.setItem(storageKey, 'true');
                        }
                    }
                ]
            });
        };

        // Auto-start check
        startStreamTour();

        // Manual trigger event listener
        $(document).on('jitsi:startTour', function() {
            startStreamTour(true);
        });
    });
</script>

<?php

use humhub\libs\Html;
use humhub\widgets\Button;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;

/* @var $model \humhubContrib\modules\jitsiMeetCloud8x8\models\JoinRoomForm */
/* @var $activeStreams \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream[] */
/* @var $endedStreams \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream[] */

$assets = \humhubContrib\modules\jitsiMeetCloud8x8\assets\Assets::register($this);
?>
<div class="container">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Open conference room'); ?>
            </div>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(['layout' => 'horizontal', 'id' => 'jrform']); ?>

                <?= $form->field($model, 'room'); ?>
                <?= $form->field($model, 'newWindow')->checkbox(); ?>
                
                <?= Button::save(Yii::t('JitsiMeetCloud8x8Module.base', 'Join'))->loader(false)->submit() ?>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
        
        <!-- Live Streams Grid -->
        <div class="live-stream-section">
            <div class="live-stream-grid">
                
                <?php foreach ($activeStreams as $stream): ?>
                <div class="stream-card live">
                    <div class="stream-badge">LIVE</div>
                    
                    <div class="stream-title"><?= Html::encode($stream->room_name) ?></div>
                    <div class="stream-info">
                        <?php if ($stream->creator): ?>
                            <?= Html::encode($stream->creator->displayName) ?>
                        <?php else: ?>
                            The Oil Press
                        <?php endif; ?>
                        <br>
                        Started: <?= Yii::$app->formatter->asTime($stream->start_time) ?>
                    </div>
                    
                    <a href="<?= Url::to(['/jitsi-meet-cloud-8x8/room/open', 'name' => $stream->room_name]) ?>" class="btn btn-default btn-stream-live" target="_blank">
                        JOIN LIVE STREAM
                    </a>
                </div>
                <?php endforeach; ?>

                <?php foreach ($endedStreams as $stream): ?>
                <div class="stream-card ended">
                    <div class="stream-badge">ENDED LIVE</div>
                    
                    <div class="stream-title"><?= Html::encode($stream->room_name) ?></div>
                    <div class="stream-info">
                        Ended: <?= Yii::$app->formatter->asDate($stream->end_time, 'medium') ?>
                        <br>
                        Duration: <?= $stream->getDuration() ?>
                    </div>
                    
                    <?php if (!empty($stream->recording_url)): ?>
                        <a href="<?= Html::encode($stream->recording_url) ?>" class="btn btn-default btn-stream-replay" target="_blank">
                            WATCH REPLAY
                        </a>
                    <?php else: ?>
                        <button class="btn btn-default btn-stream-replay" disabled>
                            WATCH REPLAY <small>(Coming Soon)</small>
                        </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <!-- Placeholders to fill grid if few items (Optional, based on screenshot) -->
                <?php for($i=0; $i < max(0, 4 - count($activeStreams) - count($endedStreams)); $i++): ?>
                 <div class="stream-card ended" style="opacity: 0.3; border-style: dashed;">
                 </div>
                <?php endfor; ?>

            </div>
        </div>
        
    </div>
</div>

<script <?= Html::nonce() ?>>
    $('#jrform').on('beforeSubmit', function(e) {
        if ($('#joinroomform-newwindow'). prop("checked") == true) {
            $('#jrform').attr('target','_blank');
        }
        return true;
    });
</script>

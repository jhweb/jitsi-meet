<?php

use humhub\libs\Html;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;
use yii\widgets\LinkPager;

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
                    
                    <div class="stream-title stream-title-overflow" title="<?= Html::encode($stream->title ?: $stream->room_name) ?>">
                        <?= Html::encode($stream->title ?: $stream->room_name) ?>
                    </div>
                    <div class="stream-room-name-sub">
                        Room name: <?= Html::encode($stream->room_name) ?>
                    </div>
                    <div class="stream-info">
                        <?php if ($stream->creator): ?>
                            <?= Html::encode($stream->creator->displayName) ?>
                        <?php else: ?>
                            The Oil Press
                        <?php endif; ?>
                        <br>
                        Started: <?= Yii::$app->formatter->asTime($stream->start_time) ?>
                        <br>
                        Connected Users: <?= $stream->active_count > 0 ? $stream->active_count : 0 ?>
                    </div>
                    
                    <a href="<?= Url::to(['/jitsi-meet-cloud-8x8/room/open', 'name' => $stream->room_name]) ?>" class="btn btn-default btn-stream-live" target="_blank">
                        JOIN LIVE STREAM
                    </a>
                </div>
                <?php endforeach; ?>

                <?php foreach ($endedStreams as $stream): ?>
                <?php 
                    $hasDownloads = (!empty($stream->recording_url) || !empty($stream->transcription_url) || !empty($stream->chat_log_url) || !empty($stream->file_urls));
                    
                    // Check expiration first (24h validity)
                    $isExpired = false;
                    if (!empty($stream->end_time)) {
                        $secondsSinceEnd = time() - strtotime($stream->end_time);
                        if ($secondsSinceEnd > (24 * 60 * 60)) {
                            $isExpired = true;
                        }
                    }

                ?>
                <div class="stream-card ended">
                    <div class="stream-badge">ENDED LIVE</div>
                    
                    <div class="stream-creator" style="font-size: 12px; margin-bottom: 5px; color: #ccc;">
                        <?php if ($stream->creator): ?>
                            <?= Html::encode($stream->creator->displayName) ?>
                        <?php else: ?>
                            The Oil Press
                        <?php endif; ?>
                    </div>

                    <div class="stream-title stream-title-overflow" title="<?= Html::encode($stream->title ?: $stream->room_name) ?>">
                        <?= Html::encode($stream->title ?: $stream->room_name) ?>
                    </div>
                    <div class="stream-room-name-sub">
                        Room name: <?= Html::encode($stream->room_name) ?>
                    </div>

                    <div class="stream-info">
                        Ended: <?= Yii::$app->formatter->asDatetime($stream->end_time, 'medium') ?>
                        <br>
                        Duration: <?= $stream->getDuration() ?>
                        <br>
                        Total Participants: <?= $stream->participant_count > 0 ? $stream->participant_count : 0 ?>
                    </div>
                    
                    
                    <?= ModalButton::primary('DOWNLOAD FILES')
                        ->load(Url::to(['details', 'id' => $stream->id]))
                        ->cssClass('btn btn-default btn-stream-replay')
                        ->options([
                            'style' => 'font-size: 10px; padding: 6px 10px; white-space: normal; line-height: 1.2;',
                        ]) 
                    ?>
                </div>
                <?php endforeach; ?>

                
                <!-- Placeholders to fill grid if few items (Optional, based on screenshot) -->
                <?php for($i=0; $i < max(0, 4 - count($activeStreams) - count($endedStreams)); $i++): ?>
                 <div class="stream-card ended" style="opacity: 0.3; border-style: dashed;">
                 </div>
                <?php endfor; ?>

            </div>
            
            <div class="pagination-container" style="text-align: center; width: 100%;">
                <?= LinkPager::widget([
                    'pagination' => $pages,
                    'options' => ['class' => 'pagination', 'style' => 'display: inline-block;'],
                    'maxButtonCount' => 5,
                ]); ?>
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

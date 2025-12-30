<?php

use humhub\libs\Html;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use humhub\modules\user\widgets\Image;

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
                    
                    <div class="stream-creator" style="font-size: 12px; margin-bottom: 5px; color: #ccc;">
                        <?php if ($stream->creator): ?>
                            <a href="<?= $stream->creator->getUrl() ?>" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center;">
                                <?= Image::widget(['user' => $stream->creator, 'width' => 20, 'link' => false]) ?>
                                <span style="margin-left: 5px;"><?= Html::encode($stream->creator->displayName) ?></span>
                            </a>
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

                    // Check for data availability (icons)
                    $hasRecording = ($stream->has_recording || !empty($stream->recording_url));
                    $hasHighlights = !empty($stream->highlights_url);
                    $hasChat = !empty($stream->chat_log_url);
                    $hasSessionData = (($stream->participant_count > 1) || !empty($stream->reactions));
                    $hasTranscript = !empty($stream->transcription_url);
                    $hasExtraFiles = !empty($stream->file_urls);

                    // Show button if any data/icon is "active"
                    // User request: "if no data to download or display see icons, then download files button should become hidden"
                    $showDownloadButton = ($hasRecording || $hasHighlights || $hasChat || $hasSessionData || $hasTranscript || $hasExtraFiles);
                ?>
                <div class="stream-card ended">
                    <div class="stream-badge">ENDED LIVE</div>
                    
                    <div class="stream-creator" style="font-size: 12px; margin-bottom: 5px; color: #ccc;">
                        <?php if ($stream->creator): ?>
                            <a href="<?= $stream->creator->getUrl() ?>" style="color: inherit; text-decoration: none; display: inline-flex; align-items: center;">
                                <?= Image::widget(['user' => $stream->creator, 'width' => 20, 'link' => false]) ?>
                                <span style="margin-left: 5px;"><?= Html::encode($stream->creator->displayName) ?></span>
                            </a>
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
                    
                    <div class="stream-indicators">
                        <!-- Video Recording -->
                        <i class="fa fa-video-camera indicator-icon <?= $hasRecording ? 'active' : '' ?>" title="Video Recording"></i>
                        
                        <!-- Highlights -->
                        <i class="fa fa-film indicator-icon <?= $hasHighlights ? 'active' : '' ?>" title="Highlights"></i>
                        
                        <!-- Chat Log -->
                        <i class="fa fa-comments indicator-icon <?= $hasChat ? 'active' : '' ?>" title="Chat Log"></i>
                        
                        <!-- Session Data -->
                        <i class="fa fa-bar-chart indicator-icon <?= $hasSessionData ? 'active' : '' ?>" title="Session Data"></i>
                        
                        <!-- Transcript -->
                        <i class="fa fa-file-text-o indicator-icon <?= $hasTranscript ? 'active' : '' ?>" title="Transcript"></i>
                    </div>
                    
                    
                    <?php if ($showDownloadButton): ?>
                    <?= ModalButton::primary('DOWNLOAD FILES')
                        ->load(Url::to(['details', 'id' => $stream->id]))
                        ->cssClass('btn btn-default btn-stream-replay')
                        ->options([
                            'style' => 'font-size: 10px; padding: 6px 10px; white-space: normal; line-height: 1.2;',
                        ]) 
                    ?>
                    <?php else: ?>
                    <div style="height: 32px;"></div> <!-- Spacer to keep card height consistent if needed, or remove -->
                    <?php endif; ?>
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

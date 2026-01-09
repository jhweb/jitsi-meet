<?php
use humhub\libs\Html;
use humhub\widgets\ModalButton;
use yii\helpers\Url;
use humhub\modules\user\widgets\Image;
use humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream;

/* @var $stream JitsiLiveStream */

$isScheduled = ($stream->status == JitsiLiveStream::STATUS_SCHEDULED);
$isLive = ($stream->status == JitsiLiveStream::STATUS_LIVE);
$isEnded = ($stream->status == JitsiLiveStream::STATUS_ENDED);

// For ended streams, calculate download availability
if ($isEnded) {
    $hasDownloads = (!empty($stream->recording_url) || !empty($stream->transcription_url) || !empty($stream->chat_log_url) || !empty($stream->file_urls));
    
    $isExpired = false;
    if (!empty($stream->end_time)) {
        $secondsSinceEnd = time() - strtotime($stream->end_time);
        if ($secondsSinceEnd > (24 * 60 * 60)) {
            $isExpired = true;
        }
    }

    $hasRecording = ($stream->has_recording || !empty($stream->recording_url));
    $hasHighlights = !empty($stream->highlights_url);
    $hasChat = !empty($stream->chat_log_url);
    $hasSessionData = (($stream->participant_count > 1) || !empty($stream->reactions));
    $hasTranscript = !empty($stream->transcription_url);
    $hasExtraFiles = !empty($stream->file_urls);

    $showDownloadButton = ($hasRecording || $hasHighlights || $hasChat || $hasSessionData || $hasTranscript || $hasExtraFiles);
}
?>

<?php if ($isScheduled): ?>
    <!-- Scheduled Card -->
    <div class="stream-card scheduled">
        <div class="stream-badge scheduled-badge">
            <i class="fa fa-clock-o"></i> SCHEDULED
        </div>
        
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
        
        <div class="stream-title stream-title-overflow" title="<?= Html::encode($stream->getTitle()) ?>">
            <?= Html::encode($stream->getTitle()) ?>
        </div>
        <div class="stream-room-name-sub">
            Room name: <?= Html::encode($stream->room_name) ?>
        </div>
        
        <div class="stream-info">
            <div class="countdown-display" style="font-size: 14px; font-weight: bold; color: #4a90d9; margin: 10px 0;">
                <i class="fa fa-hourglass-half"></i>
                <?= $stream->getCountdown() ?>
            </div>
            <?php if ($stream->scheduled_start): ?>
            <small>
                <?= Yii::$app->formatter->asDatetime($stream->scheduled_start, 'medium') ?>
            </small>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 10px;">
            <?php 
            if (!empty($stream->calendar_entry_id) && $stream->calendarEntry) {
                $calendarEntry = $stream->calendarEntry;
                $container = $calendarEntry->content->container;
                
                // Check if user is attending (State 2 = Accepted usually, we accept anything > 0 as "responded" or checks specific state)
                // Using Query Builder for safety (assuming table exists)
                $isAttending = (new \yii\db\Query())
                    ->from('calendar_entry_participant')
                    ->where(['calendar_entry_id' => $calendarEntry->id, 'user_id' => Yii::$app->user->id])
                    ->andWhere(['participation_state' => 2]) // 2 = Accepted
                    ->exists();

                $isCreator = ($stream->creator_id == Yii::$app->user->id);

                if ($isAttending || $isCreator) {
                     // Show View Event Button - Use custom modal
                     $viewEventUrl = Url::to(['/jitsi-meet-cloud-8x8/room/view-event', 'id' => $stream->id]);
                     
                     echo \humhub\widgets\ModalButton::defaultType('<i class="fa fa-calendar"></i> ' . Yii::t('JitsiMeetCloud8x8Module.base', 'View Event'))
                        ->load($viewEventUrl)
                        ->cssClass('btn btn-default btn-sm')
                        ->options(['style' => 'width: 100%; font-size: 11px; white-space: normal; color: #333; background-color: #fff; border: 1px solid #ccc;']);
                } else {
                    // Show Attend Button - Use custom RSVP endpoint
                    $attendUrl = Url::to(['/jitsi-meet-cloud-8x8/room/attend', 'id' => $stream->id, 'type' => 2]);
                    
                    echo \humhub\libs\Html::a('<i class="fa fa-check"></i> ' . Yii::t('JitsiMeetCloud8x8Module.base', 'Attend'), $attendUrl, [
                        'class' => 'btn btn-primary btn-sm',
                        'style' => 'width: 100%; font-size: 11px; white-space: normal;',
                        'data-method' => 'post',
                    ]);
                }
            } else {
                // Fallback for streams without calendar entry
                 ?>
                <a href="<?= Url::to(['/jitsi-meet-cloud-8x8/room/open', 'name' => $stream->room_name]) ?>" 
                   class="btn btn-primary btn-sm" target="_blank" style="width: 100%; font-size: 11px; white-space: normal;">
                    <i class="fa fa-video-camera"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'JOIN ROOM') ?>
                </a>
                <?php
            }
            ?>
        </div>
    </div>

<?php elseif ($isLive): ?>
    <!-- Live Card -->
    <div class="stream-card live">
        <div class="stream-badge">
            <span class="pulsating-dot"></span> LIVE
        </div>
        
        <?php if (!empty($stream->ytstream_url)): ?>
        <a href="<?= Html::encode($stream->ytstream_url) ?>" target="_blank" class="live-yt-icon" style="position: absolute; top: 10px; right: 10px; font-size: 20px; color: #ff0000;" title="Watch on YouTube">
            <i class="fa fa-youtube-play"></i>
        </a>
        <?php endif; ?>
        
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
        
        <div class="stream-title stream-title-overflow" title="<?= Html::encode($stream->getTitle()) ?>">
            <?= Html::encode($stream->getTitle()) ?>
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

<?php else: ?>
    <!-- Ended Card -->
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

        <div class="stream-title stream-title-overflow" title="<?= Html::encode($stream->getTitle()) ?>">
            <?= Html::encode($stream->getTitle()) ?>
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
            <?php if (!empty($stream->ytstream_url)): ?>
                <i class="fa fa-youtube-play indicator-icon active" title="YouTube Live Stream" style="color: #ff0000 !important;"></i>
            <?php endif; ?>
            
            <i class="fa fa-video-camera indicator-icon <?= $hasRecording ? 'active' : '' ?>" title="Video Recording"></i>
            <i class="fa fa-film indicator-icon <?= $hasHighlights ? 'active' : '' ?>" title="Highlights"></i>
            <i class="fa fa-comments indicator-icon <?= $hasChat ? 'active' : '' ?>" title="Chat Log"></i>
            <i class="fa fa-bar-chart indicator-icon <?= $hasSessionData ? 'active' : '' ?>" title="Session Data"></i>
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
        <div style="height: 32px;"></div>
        <?php endif; ?>
    </div>
<?php endif; ?>


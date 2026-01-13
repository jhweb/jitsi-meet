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

// 30-Minute Rule: Override Live status if Premature
if ($isLive && $stream->scheduled_start) {
    if (strtotime($stream->scheduled_start) > (time() + 1800)) {
        $isLive = false;
        $isScheduled = true;
    }
}

// Logic for Ended Streams (Downloads/Expiration)
$hasDownloads = false;
$showDownloadButton = false;
$isExpired = false;

if ($isEnded) {
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

// Determine Status Badge & Class
$statusClass = '';
$statusLabel = '';
if ($isLive) {
    $statusClass = 'live';
    $statusLabel = 'LIVE';
} elseif ($isScheduled) {
    $statusClass = 'scheduled';
    $statusLabel = 'SCHEDULED';
} else {
    $statusClass = 'ended';
    $statusLabel = 'ENDED LIVE';
}

// Determine Creator
$creatorName = $stream->creator ? $stream->creator->displayName : 'The Oil Press';
?>

<div class="stream-card-v2 <?= $statusClass ?>">
    
    <!-- Delete Button (Preserved Logic) -->
    <?php 
    $canDelete = false;
    if (!Yii::$app->user->isGuest) {
        if ($stream->creator_id == Yii::$app->user->id) {
            $canDelete = true;
        } elseif (Yii::$app->user->isAdmin()) {
            $canDelete = true;
        } elseif ($stream->space && $stream->space->isAdmin()) {
            $canDelete = true;
        } elseif ($stream->calendarEntry && $stream->calendarEntry->content->container->can(\humhub\modules\content\permissions\ManageContent::class)) {
            $canDelete = true;
        }
    }
    
    if ($canDelete && $isScheduled): ?>
        <?= Html::a('<i class="fa fa-times"></i>', Url::to(['/jitsi-meet-cloud-8x8/room/delete', 'id' => $stream->id]), [
            'class' => 'stream-delete-btn',
            'data-method' => 'post',
            'data-confirm' => Yii::t('JitsiMeetCloud8x8Module.base', 'Are you sure you want to delete this scheduled stream?'),
            'title' => Yii::t('JitsiMeetCloud8x8Module.base', 'Delete Stream'),
            'style' => 'position: absolute; top: 10px; right: 35px; color: #ff0000; cursor: pointer; z-index: 100; font-size: 14px;'
        ]) ?>
    <?php endif; ?>

    <!-- HEADER: Creator & Status -->
    <div class="col-header">
        <div class="creator-info">
            <div class="creator-avatar">
                <?php if ($stream->creator): ?>
                    <?= Image::widget(['user' => $stream->creator, 'width' => 36, 'link' => true]) ?>
                <?php else: ?>
                    <img src="<?= Yii::$app->view->theme->baseUrl ?>/img/default_user.jpg" alt="System" style="width: 36px; height: 36px; border-radius: 50%;">
                <?php endif; ?>
            </div>
            <div class="creator-name">
                <?= Html::encode($creatorName) ?>
            </div>
        </div>
        <div class="status-badge <?= $statusClass ?>">
            <?php if ($isLive): ?><span class="pulsating-dot"></span><?php endif; ?>
            <?= $statusLabel ?>
        </div>
    </div>

    <!-- BODY: Title & Meta -->
    <div class="col-body">
        <div class="stream-title-v2" title="<?= Html::encode($stream->getTitle()) ?>">
            <?= Html::encode($stream->getTitle()) ?>
        </div>
        <div class="room-name-meta">
            Room name: <?= Html::encode($stream->room_name) ?>
        </div>

        <!-- Meta Grid -->
        <div class="stream-meta-grid">
            <?php if ($isLive): ?>
                <div class="meta-item">
                    <i class="fa fa-clock-o"></i> 
                    Started: <?= Yii::$app->formatter->asTime($stream->start_time) ?>
                </div>
                <div class="meta-item">
                    <i class="fa fa-users"></i> 
                    Users: <?= $stream->active_count > 0 ? $stream->active_count : 0 ?>
                </div>
            <?php elseif ($isScheduled): ?>
                 <div class="meta-item" style="grid-column: span 2;">
                     <div class="countdown-timer">
                        <i class="fa fa-hourglass-half"></i> <?= $stream->getCountdown() ?>
                     </div>
                 </div>
                 <?php if ($stream->scheduled_start): ?>
                 <div class="meta-item" style="grid-column: span 2;">
                    <small><?= Yii::$app->formatter->asDatetime($stream->scheduled_start, 'medium') ?></small>
                 </div>
                 <?php endif; ?>
            <?php else: /* Ended */ ?>
                <div class="meta-item" style="grid-column: span 2;">
                    Ended: <?= Yii::$app->formatter->asDatetime($stream->end_time, 'medium') ?>
                </div>
                <div class="meta-item">
                    <i class="fa fa-clock-o"></i> <?= $stream->getDuration() ?>
                </div>
                <div class="meta-item">
                    <i class="fa fa-users"></i> <?= $stream->participant_count > 0 ? $stream->participant_count : 0 ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOOTER: Action Button -->
    <div class="col-footer">
        <?php if ($isLive): ?>
            <a href="<?= Url::to(['/jitsi-meet-cloud-8x8/room/open', 'name' => $stream->room_name]) ?>" 
               class="card-action-btn live" target="_blank">
               <i class="fa fa-video-camera"></i> JOIN LIVE STREAM
            </a>
            <?php if (!empty($stream->ytstream_url)): ?>
                <a href="<?= Html::encode($stream->ytstream_url) ?>" target="_blank" class="card-action-btn view" style="text-align: center; font-size: 11px; padding: 5px;">
                    <i class="fa fa-youtube-play" style="color: red;"></i> Watch on YouTube
                </a>
            <?php endif; ?>

        <?php elseif ($isScheduled): ?>
            <?php 
            // Calendar Attendance Logic
            if (!empty($stream->calendar_entry_id) && $stream->calendarEntry) {
                $calendarEntry = $stream->calendarEntry;
                $isAttending = (new \yii\db\Query())
                    ->from('calendar_entry_participant')
                    ->where(['calendar_entry_id' => $calendarEntry->id, 'user_id' => Yii::$app->user->id])
                    ->andWhere(['participation_state' => \humhub\modules\calendar\models\CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED])
                    ->exists();

                $isCreator = ($stream->creator_id == Yii::$app->user->id);

                if ($isAttending || $isCreator) {
                     // VIEW EVENT
                     echo ModalButton::defaultType('VIEW EVENT')
                        ->load(Url::to(['/jitsi-meet-cloud-8x8/room/view-event', 'id' => $stream->id]))
                        ->cssClass('card-action-btn view');
                } else {
                    // ATTEND
                    echo Html::a('<i class="fa fa-check-circle"></i> ATTEND', 
                        Url::to(['/jitsi-meet-cloud-8x8/room/attend', 'id' => $stream->id, 'type' => \humhub\modules\calendar\models\CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED]), 
                        ['class' => 'card-action-btn attend', 'data-method' => 'post']);
                }
            } else {
                // Fallback Join
                ?>
                <a href="<?= Url::to(['/jitsi-meet-cloud-8x8/room/open', 'name' => $stream->room_name]) ?>" 
                   class="card-action-btn attend" target="_blank">
                   JOIN ROOM
                </a>
                <?php
            }
            ?>

        <?php else: /* Ended */ ?>
            <?php if ($showDownloadButton): ?>
                <?= ModalButton::defaultType('<i class="fa fa-download"></i> DOWNLOAD FILES')
                    ->load(Url::to(['details', 'id' => $stream->id]))
                    ->cssClass('card-action-btn view')
                ?>
            <?php else: ?>
                <div class="card-action-btn view" style="opacity: 0.5; cursor: default;">
                    <i class="fa fa-ban"></i> No Files Available
                </div>
            <?php endif; ?>

            <!-- Indicators Row -->
            <div class="download-bar">
                <?php if (!empty($stream->ytstream_url)): ?>
                    <i class="fa fa-youtube-play icon-indicator active" style="color: red;" title="YouTube"></i>
                <?php endif; ?>
                <i class="fa fa-video-camera icon-indicator <?= ($hasRecording) ? 'active' : '' ?>" title="Recording"></i>
                <i class="fa fa-film icon-indicator <?= ($hasHighlights) ? 'active' : '' ?>" title="Highlights"></i>
                <i class="fa fa-comments icon-indicator <?= ($hasChat) ? 'active' : '' ?>" title="Chat"></i>
                <i class="fa fa-bar-chart icon-indicator <?= ($hasSessionData) ? 'active' : '' ?>" title="Stats"></i>
                <i class="fa fa-file-text-o icon-indicator <?= ($hasTranscript) ? 'active' : '' ?>" title="Transcript"></i>
            </div>
        <?php endif; ?>
    </div>
</div>

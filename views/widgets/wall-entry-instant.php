<?php

use humhub\widgets\Button;
use humhub\widgets\Label;
use yii\helpers\Html;
use yii\helpers\Url;
use Yii;

/** @var \humhubContrib\modules\jitsiMeetCloud8x8\models\InstantVideoChat $model */
?>

<div class="wall-entry-instant-video-chat">
    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="media">
                <div class="media-left">
                    <i class="fa fa-video-camera fa-2x text-primary"></i>
                </div>
                <div class="media-body">
                    <h4 class="media-heading">
                        <?= Html::encode($model->getDisplayTitle()) ?>
                        <?php if ($model->isActive()): ?>
                            <?= Label::asSuccess(Yii::t('JitsiMeetCloud8x8Module.base', 'LIVE'))->cssClass('label-live') ?>
                        <?php endif; ?>
                    </h4>
                    <div class="text-muted">
                        <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Started by {user}', ['user' => $model->createdBy->displayName]) ?>
                        <?php if ($model->started_at): ?>
                            • <?= Yii::$app->formatter->asRelativeTime($model->started_at) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="panel-body">
            <?php if ($model->description): ?>
                <div class="video-chat-description">
                    <?= Html::encode($model->description) ?>
                </div>
            <?php endif; ?>
            
            <div class="video-chat-info">
                <div class="row">
                    <div class="col-md-8">
                        <?php if ($model->participant_count > 0): ?>
                            <span class="participant-count">
                                <i class="fa fa-users"></i>
                                <?= $model->participant_count ?>
                                <?= Yii::t('JitsiMeetCloud8x8Module.base', 'participant(s)') ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($model->started_at): ?>
                            <span class="time-started">
                                <i class="fa fa-clock-o"></i>
                                <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Started {time}', ['time' => Yii::$app->formatter->asRelativeTime($model->started_at)]) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4 text-right">
                        <?php if ($model->isActive()): ?>
                            <?= Button::asLink(Yii::t('JitsiMeetCloud8x8Module.base', 'Join Now'))
                                ->icon('video-camera')
                                ->cssClass('btn btn-primary btn-sm')
                                ->link($model->getJoinUrl())
                                ->options([
                                    'target' => '_blank',
                                    'title' => Yii::t('JitsiMeetCloud8x8Module.base', 'Join the video chat')
                                ]) ?>
                        <?php else: ?>
                            <span class="text-muted">
                                <i class="fa fa-stop"></i>
                                <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Ended') ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($model->canEnd()): ?>
                            <?= Button::asLink(Yii::t('JitsiMeetCloud8x8Module.base', 'End Chat'))
                                ->icon('stop')
                                ->cssClass('btn btn-danger btn-sm')
                                ->action('jitsi-meet-cloud-8x8.space.endChat', ['id' => $model->id])
                                ->options([
                                    'title' => Yii::t('JitsiMeetCloud8x8Module.base', 'End this video chat'),
                                    'data-confirm' => Yii::t('JitsiMeetCloud8x8Module.base', 'Are you sure you want to end this video chat?')
                                ]) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.wall-entry-instant-video-chat .label-live {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.wall-entry-instant-video-chat .participant-count,
.wall-entry-instant-video-chat .time-started {
    margin-right: 15px;
    color: #666;
}

.wall-entry-instant-video-chat .video-chat-description {
    margin-bottom: 15px;
    font-style: italic;
    color: #555;
}

.wall-entry-instant-video-chat .video-chat-info {
    border-top: 1px solid #eee;
    padding-top: 10px;
    margin-top: 10px;
}
</style>

<script>
$(document).ready(function() {
    // Handle end chat button
    $('.wall-entry-instant-video-chat').on('click', '[data-action="jitsi-meet-cloud-8x8.space.endChat"]', function(e) {
        e.preventDefault();
        
        var chatId = $(this).data('id');
        var button = $(this);
        
        if (!confirm('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Are you sure you want to end this video chat?') ?>')) {
            return;
        }
        
        // Show loading state
        button.prop('disabled', true).text('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Ending...') ?>');
        
        $.ajax({
            url: '<?= Url::to(['/jitsi-meet-cloud-8x8/space/end-chat']) ?>',
            method: 'POST',
            data: {
                id: chatId,
                <?= Yii::$app->request->csrfParam ?>: '<?= Yii::$app->request->csrfToken ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Reload the page to update the wall entry
                    location.reload();
                } else {
                    // Show error message
                    humhub.modules.ui.status.error(response.error || '<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Failed to end video chat') ?>');
                    
                    // Reset button
                    button.prop('disabled', false).text('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'End Chat') ?>');
                }
            },
            error: function() {
                // Show error message
                humhub.modules.ui.status.error('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Failed to end video chat') ?>');
                
                // Reset button
                button.prop('disabled', false).text('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'End Chat') ?>');
            }
        });
    });
});
</script>


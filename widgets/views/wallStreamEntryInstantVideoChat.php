<?php

use humhubContrib\modules\jitsiMeetCloud8x8\models\InstantVideoChat;
use humhubContrib\modules\jitsiMeetCloud8x8\widgets\WallStreamEntryInstantVideoChat;
use humhub\modules\ui\icon\widgets\Icon;
use yii\helpers\Html;

/* @var InstantVideoChat $model */
/* @var WallStreamEntryInstantVideoChat $widget */
?>

<div class="jitsi-video-chat-entry">
    <div class="video-chat-header">
        <div class="video-chat-title-section">
            <h3 class="video-chat-title">
                <?= Icon::get('video-camera') ?>
                <?= Html::encode($model->title ?: $model->room_name) ?>
            </h3>
            
            <?php if ($model->status === 'active'): ?>
                <span class="live-indicator">
                    <span class="live-dot"></span>
                    LIVE
                </span>
            <?php else: ?>
                <span class="ended-indicator">
                    <i class="fa fa-stop-circle"></i>
                    Ended
                </span>
            <?php endif; ?>
        </div>
        
        <div class="video-chat-meta">
            <span class="participant-count">
                <i class="fa fa-users"></i>
                <?= $model->participant_count ?> participant<?= $model->participant_count !== 1 ? 's' : '' ?>
            </span>
            
            <?php if ($model->started_at): ?>
                <span class="started-time">
                    <i class="fa fa-clock-o"></i>
                    Started <?= Yii::$app->formatter->asRelativeTime($model->started_at) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="video-chat-actions">
        <?php if ($model->status === 'active'): ?>
            <a href="<?= $model->getJoinUrl() ?>" 
               class="btn btn-primary btn-lg join-button"
               target="_blank">
                <i class="fa fa-video-camera"></i>
                Join Video Chat
            </a>
        <?php else: ?>
            <span class="btn btn-default btn-lg disabled">
                <i class="fa fa-video-camera"></i>
                Chat Ended
            </span>
        <?php endif; ?>
        
        <?php if ($model->canEnd()): ?>
            <button class="btn btn-warning end-chat-btn" 
                    data-chat-id="<?= $model->id ?>"
                    data-action-click="jitsi-meet-cloud-8x8.Chat.endChat"
                    data-action-url="<?= $model->getEndUrl() ?>">
                <i class="fa fa-stop"></i>
                End Chat
            </button>
        <?php endif; ?>
        
        <?php if ($model->canDelete()): ?>
            <button class="btn btn-danger delete-chat-btn" 
                    data-chat-id="<?= $model->id ?>"
                    data-action-click="jitsi-meet-cloud-8x8.Chat.deleteChat"
                    data-action-url="<?= $model->getDeleteUrl() ?>">
                <i class="fa fa-trash"></i>
                Delete
            </button>
        <?php endif; ?>
    </div>
    
    <?php if ($model->description): ?>
        <div class="video-chat-description">
            <?= $model->description ?>
        </div>
    <?php endif; ?>
</div>

<style>
.jitsi-video-chat-entry {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin: 10px 0;
}

.video-chat-header {
    margin-bottom: 15px;
}

.video-chat-title-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.video-chat-title {
    font-size: 1.4em;
    font-weight: bold;
    margin: 0;
    color: #2c3e50;
}

.live-indicator {
    background: #e74c3c;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.9em;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 5px;
}

.live-dot {
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.ended-indicator {
    background: #95a5a6;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.9em;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 5px;
}

.video-chat-meta {
    display: flex;
    gap: 20px;
    color: #7f8c8d;
    font-size: 0.9em;
}

.video-chat-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.video-chat-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.join-button {
    background: #27ae60;
    border-color: #27ae60;
    font-weight: bold;
}

.join-button:hover {
    background: #229954;
    border-color: #229954;
}

.video-chat-description {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e9ecef;
    color: #5a6c7d;
}
</style>

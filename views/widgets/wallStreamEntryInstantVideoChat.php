<?php

use humhubContrib\modules\jitsiMeetCloud8x8\models\InstantVideoChat;
use humhub\widgets\Button;
use humhub\libs\Html;
use Yii;

/** @var InstantVideoChat $model */
?>

<div class="wall-entry-content">
    <div class="video-chat-entry">
        <div class="video-chat-header">
            <h4><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Video Chat') ?></h4>
            <?php if ($model->title): ?>
                <h5><?= Html::encode($model->title) ?></h5>
            <?php endif; ?>
        </div>
        
        <div class="video-chat-body">
            <?php if ($model->description): ?>
                <p><?= Html::encode($model->description) ?></p>
            <?php endif; ?>
            
            <div class="video-chat-info">
                <span class="room-name">
                    <strong><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Room') ?>:</strong> 
                    <?= Html::encode($model->room_name) ?>
                </span>
                
                <?php if ($model->participant_count > 0): ?>
                    <span class="participant-count">
                        <strong><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Participants') ?>:</strong> 
                        <?= $model->participant_count ?>
                    </span>
                <?php endif; ?>
                
                <span class="status">
                    <strong><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Status') ?>:</strong> 
                    <?= $model->status === 'active' ? 
                        Yii::t('JitsiMeetCloud8x8Module.base', 'Active') : 
                        Yii::t('JitsiMeetCloud8x8Module.base', 'Ended') ?>
                </span>
            </div>
        </div>
        
        <div class="video-chat-actions">
            <?php if ($model->status === 'active'): ?>
                <?= Button::primary(Yii::t('JitsiMeetCloud8x8Module.base', 'Join Video Chat'))
                    ->icon('video-camera')
                    ->link($model->getUrl())
                    ->options(['target' => '_blank']) ?>
            <?php else: ?>
                <?= Button::defaultButton(Yii::t('JitsiMeetCloud8x8Module.base', 'Video Chat Ended'))
                    ->icon('video-camera')
                    ->disabled() ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var \humhub\modules\content\components\ContentContainerActiveRecord $contentContainer */
?>

<div class="quick-video-chat-form">
    <div class="form-group">
        <?= Html::label(Yii::t('JitsiMeetCloud8x8Module.base', 'Room Title (optional)'), 'video-chat-title', ['class' => 'control-label']) ?>
        <?= Html::textInput('title', '', [
            'id' => 'video-chat-title',
            'class' => 'form-control',
            'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Auto-generated if empty')
        ]) ?>
        <div class="help-block">
            <?= Yii::t('JitsiMeetCloud8x8Module.base', 'If left empty, a room name will be generated automatically') ?>
        </div>
    </div>
    
    <div class="form-group">
        <?= Html::label(Yii::t('JitsiMeetCloud8x8Module.base', 'Description (optional)'), 'video-chat-description', ['class' => 'control-label']) ?>
        <?= Html::textarea('description', '', [
            'id' => 'video-chat-description',
            'class' => 'form-control',
            'rows' => 3,
            'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Join us for a video chat!')
        ]) ?>
        <div class="help-block">
            <?= Yii::t('JitsiMeetCloud8x8Module.base', 'This message will appear in the space stream') ?>
        </div>
    </div>
    
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        <?= Yii::t('JitsiMeetCloud8x8Module.base', 'The video chat will be posted to the space stream and all members will be notified.') ?>
    </div>
</div>


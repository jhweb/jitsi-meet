<?php

use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\modules\content\widgets\WallCreateContentFormFooter;
use yii\helpers\Html;

/* @var $this \humhub\components\View */
/* @var $model \humhubContrib\modules\jitsiMeetCloud8x8\models\InstantVideoChat */
/* @var $form ActiveForm */
?>

<div class="contentForm_options" data-content-component="jitsi-meet-cloud-8x8">
    <?= $form->field($model, 'room_name')->textInput([
        'id' => 'video-chat-room-name',
        'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Room Name (required)'),
        'required' => true,
    ])->label(Yii::t('JitsiMeetCloud8x8Module.base', 'Room Name')) ?>

    <?= $form->field($model, 'title')->textInput([
        'id' => 'video-chat-title',
        'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Title (optional)'),
    ])->label(Yii::t('JitsiMeetCloud8x8Module.base', 'Title')) ?>

    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        <?= Yii::t('JitsiMeetCloud8x8Module.base', 'The video chat will be posted to the space stream and all members will be notified.') ?>
    </div>
</div>

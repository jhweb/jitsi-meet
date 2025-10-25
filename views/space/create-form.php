<?php

use humhub\modules\content\widgets\richtext\RichTextField;
use humhub\modules\content\widgets\WallCreateContentFormFooter;
use humhub\modules\ui\form\widgets\ActiveForm;
use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $videoChat \humhubContrib\modules\jitsiMeetCloud8x8\models\InstantVideoChat */
/* @var $contentContainer \humhub\modules\content\components\ContentContainerActiveRecord */
?>

<div class="video-chat-form">
    <?php $form = ActiveForm::begin(['action' => $contentContainer->createUrl('/jitsi-meet-cloud-8x8/space/quick-post')]); ?>
    
    <?= $form->field($videoChat, 'title')->textInput([
        'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Room Title (optional)'),
    ])->label(false) ?>

    <?= $form->field($videoChat, 'description')->widget(RichTextField::class, [
        'form' => $form,
        'layout' => RichTextField::LAYOUT_INLINE,
        'pluginOptions' => ['maxHeight' => '300px'],
        'placeholder' => Yii::t("JitsiMeetCloud8x8Module.base", "Join us for a video chat!"),
        'name' => 'description',
        'disabled' => $contentContainer->isArchived(),
        'disabledText' => Yii::t("JitsiMeetCloud8x8Module.base", "This space is archived."),
    ])->label(false) ?>

    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        <?= Yii::t('JitsiMeetCloud8x8Module.base', 'The video chat will be posted to the space stream and all members will be notified.') ?>
    </div>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('JitsiMeetCloud8x8Module.base', 'Start Video Chat'), [
            'class' => 'btn btn-primary'
        ]) ?>
        <?= Html::button(Yii::t('JitsiMeetCloud8x8Module.base', 'Cancel'), [
            'class' => 'btn btn-default',
            'data-action-click' => 'ui.modal.close'
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

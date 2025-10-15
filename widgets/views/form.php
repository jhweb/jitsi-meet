<?php

use humhub\modules\content\widgets\richtext\RichTextField;
use humhub\modules\content\widgets\WallCreateContentForm;
use humhub\modules\content\widgets\WallCreateContentFormFooter;
use humhubContrib\modules\jitsiMeetCloud8x8\models\InstantVideoChat;
use humhub\modules\ui\form\widgets\ActiveForm;
use yii\helpers\Html;

/* @var WallCreateContentForm $wallCreateContentForm */
/* @var ActiveForm $form */
/* @var InstantVideoChat $videoChat */
?>

<?= $form->field($videoChat, 'title')->textInput([
    'id' => 'video-chat-title' . ($wallCreateContentForm->isModal ? 'Modal' : ''),
    'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Room Title (optional)'),
])->label(false) ?>

<?= $form->field($videoChat, 'description')->widget(RichTextField::class, [
    'id' => 'video-chat-description' . ($wallCreateContentForm->isModal ? 'Modal' : ''),
    'form' => $form,
    'layout' => $wallCreateContentForm->isModal ? RichTextField::LAYOUT_BLOCK : RichTextField::LAYOUT_INLINE,
    'pluginOptions' => ['maxHeight' => '300px'],
    'placeholder' => Yii::t("JitsiMeetCloud8x8Module.base", "Join us for a video chat!"),
    'name' => 'description',
    'disabled' => (property_exists(Yii::$app->controller, 'contentContainer') && Yii::$app->controller->contentContainer->isArchived()),
    'disabledText' => Yii::t("JitsiMeetCloud8x8Module.base", "This space is archived."),
])->label(false) ?>

<div class="alert alert-info">
    <i class="fa fa-info-circle"></i>
    <?= Yii::t('JitsiMeetCloud8x8Module.base', 'The video chat will be posted to the space stream and all members will be notified.') ?>
</div>

<?= WallCreateContentFormFooter::widget([
    'contentContainer' => $videoChat->content->container,
    'wallCreateContentForm' => $wallCreateContentForm,
]) ?>

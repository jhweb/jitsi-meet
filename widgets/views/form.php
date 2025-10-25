<?php

use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\modules\ui\form\widgets\Textarea;
use humhub\modules\content\widgets\WallCreateContentFormFooter;
use humhub\modules\ui\form\widgets\TextInput;
use yii\helpers\Html;

/** @var \humhubContrib\modules\jitsiMeetCloud8x8\widgets\Form $wallCreateContentForm */
/** @var \humhubContrib\modules\jitsiMeetCloud8x8\models\InstantVideoChat $videoChat */
/** @var ActiveForm $form */
?>

<div class="contentForm_options" data-content-component="jitsi-meet-cloud-8x8">
    <?= $form->field($videoChat, 'room_name')->textInput([
        'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Enter room name...'),
        'maxlength' => 50
    ])->label(Yii::t('JitsiMeetCloud8x8Module.base', 'Room Name')) ?>
    
    <?= $form->field($videoChat, 'title')->textInput([
        'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Enter title (optional)...'),
        'maxlength' => 100
    ])->label(Yii::t('JitsiMeetCloud8x8Module.base', 'Title (Optional)')) ?>
    
    <?= $form->field($videoChat, 'description')->textarea([
        'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Enter description (optional)...'),
        'rows' => 3
    ])->label(Yii::t('JitsiMeetCloud8x8Module.base', 'Description (Optional)')) ?>
    
           <?= WallCreateContentFormFooter::widget([
               'submitUrl' => '/jitsi-meet-cloud-8x8/instant-video-chat/create',
               'submitButtonText' => Yii::t('JitsiMeetCloud8x8Module.base', 'Create Video Chat'),
               'contentContainer' => $wallCreateContentForm->contentContainer
           ]) ?>
</div>

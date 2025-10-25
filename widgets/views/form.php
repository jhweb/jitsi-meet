<?php

use humhub\modules\ui\form\widgets\ActiveForm;
use humhub\modules\ui\form\widgets\Textarea;
use humhub\modules\content\widgets\WallCreateContentFormFooter;
use humhub\modules\ui\form\widgets\TextInput;
use yii\helpers\Html;

/** @var \humhubContrib\modules\jitsiMeetCloud8x8\widgets\Form $wallCreateContentForm */
/** @var \humhubContrib\modules\jitsiMeetCloud8x8\models\InstantVideoChat $videoChat */
/** @var ActiveForm|null $form */
?>

<div class="contentForm_options" data-content-component="jitsi-meet-cloud-8x8">
    <?php if ($form): ?>
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
    <?php else: ?>
        <div class="form-group">
            <label class="control-label"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Room Name') ?></label>
            <input type="text" name="InstantVideoChat[room_name]" class="form-control" placeholder="<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Enter room name...') ?>" maxlength="50">
        </div>
        
        <div class="form-group">
            <label class="control-label"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Title (Optional)') ?></label>
            <input type="text" name="InstantVideoChat[title]" class="form-control" placeholder="<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Enter title (optional)...') ?>" maxlength="100">
        </div>
        
        <div class="form-group">
            <label class="control-label"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Description (Optional)') ?></label>
            <textarea name="InstantVideoChat[description]" class="form-control" placeholder="<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Enter description (optional)...') ?>" rows="3"></textarea>
        </div>
    <?php endif; ?>
    
    <div class="form-group">
        <button type="submit" class="btn btn-primary" 
                data-action-click="jitsi-meet-cloud-8x8.Form.submit"
                data-action-url="<?= $wallCreateContentForm->getSubmitUrl() ?>">
            <i class="fa fa-video-camera"></i>
            <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Create Video Chat') ?>
        </button>
    </div>
</div>

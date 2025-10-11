<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var \humhub\modules\content\components\ContentContainerActiveRecord $contentContainer */
?>

<div class="quick-video-chat-modal">
    <div class="modal-header">
        <h4 class="modal-title">
            <i class="fa fa-video-camera"></i>
            <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Start Video Chat') ?>
        </h4>
    </div>
    
    <div class="modal-body">
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
    
    <div class="modal-footer">
        <?= Html::button(Yii::t('JitsiMeetCloud8x8Module.base', 'Cancel'), [
            'class' => 'btn btn-default',
            'data-dismiss' => 'modal'
        ]) ?>
        <?= Html::button(Yii::t('JitsiMeetCloud8x8Module.base', 'Start Video Chat'), [
            'class' => 'btn btn-primary',
            'id' => 'start-video-chat-btn'
        ]) ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#start-video-chat-btn').on('click', function() {
        var title = $('#video-chat-title').val();
        var description = $('#video-chat-description').val();
        
        // Show loading state
        $(this).prop('disabled', true).text('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Starting...') ?>');
        
        $.ajax({
            url: '<?= Url::to(['/jitsi-meet-cloud-8x8/space/quick-post']) ?>',
            method: 'POST',
            data: {
                title: title,
                description: description,
                <?= Yii::$app->request->csrfParam ?>: '<?= Yii::$app->request->csrfToken ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Close modal
                    $('#quick-video-chat-modal').modal('hide');
                    
                    // Show success message
                    humhub.modules.ui.status.success(response.message);
                    
                    // Redirect to video chat
                    window.location.href = response.joinUrl;
                } else {
                    // Show error message
                    humhub.modules.ui.status.error(response.error || '<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Failed to start video chat') ?>');
                    
                    // Reset button
                    $('#start-video-chat-btn').prop('disabled', false).text('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Start Video Chat') ?>');
                }
            },
            error: function() {
                // Show error message
                humhub.modules.ui.status.error('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Failed to start video chat') ?>');
                
                // Reset button
                $('#start-video-chat-btn').prop('disabled', false).text('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Start Video Chat') ?>');
            }
        });
    });
    
    // Reset form when modal is closed
    $('#quick-video-chat-modal').on('hidden.bs.modal', function() {
        $('#video-chat-title').val('');
        $('#video-chat-description').val('');
        $('#start-video-chat-btn').prop('disabled', false).text('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Start Video Chat') ?>');
    });
});
</script>


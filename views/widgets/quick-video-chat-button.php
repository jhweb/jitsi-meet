<?php

use humhub\widgets\Button;
use humhub\widgets\Modal;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var \humhubContrib\modules\jitsiMeetCloud8x8\widgets\QuickVideoChatButton $this */
/** @var \humhub\modules\content\components\ContentContainerActiveRecord $contentContainer */
?>

<?= Button::asLink(Yii::t('JitsiMeetCloud8x8Module.base', 'Start Video Chat'))
    ->icon('video-camera')
    ->cssClass('btn btn-primary')
    ->action('jitsi-meet-cloud-8x8.space.quickPostModal')
    ->options([
        'data-toggle' => 'modal',
        'data-target' => '#quick-video-chat-modal',
        'title' => Yii::t('JitsiMeetCloud8x8Module.base', 'Start an instant video chat')
    ]) ?>

<?= Modal::widget([
    'id' => 'quick-video-chat-modal',
    'header' => Yii::t('JitsiMeetCloud8x8Module.base', 'Start Video Chat'),
    'body' => $this->render('quick-video-chat-form', [
        'contentContainer' => $contentContainer
    ]),
    'footer' => Html::button(Yii::t('JitsiMeetCloud8x8Module.base', 'Cancel'), [
        'class' => 'btn btn-default',
        'data-dismiss' => 'modal'
    ]) . ' ' . Html::button(Yii::t('JitsiMeetCloud8x8Module.base', 'Start Video Chat'), [
        'class' => 'btn btn-primary',
        'id' => 'start-video-chat-btn'
    ])
]) ?>

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


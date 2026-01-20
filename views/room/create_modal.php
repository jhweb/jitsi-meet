<?php

use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;
use yii\bootstrap\ActiveForm;
use humhub\libs\Html;

/* @var $model \humhubContrib\modules\jitsiMeetCloud8x8\models\JoinRoomForm */
?>

<?php ModalDialog::begin(['header' => Yii::t('JitsiMeetCloud8x8Module.base', 'Create Live Stream'), 'size' => 'small', 'class' => 'jitsi-modal-overrides']) ?>
    <?php $form = ActiveForm::begin(['id' => 'create-stream-form', 'action' => ['index']]); ?>
        <div class="modal-body">
            <p><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Enter a name for your live stream room.') ?></p>
            
            <?= $form->field($model, 'room')->textInput(['placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Stream Name')])->label(false) ?>
            
            <?= $form->field($model, 'newWindow')->checkbox() ?>

            <?= $form->field($model, 'lobbyEnabled')->checkbox()->hint(Yii::t('JitsiMeetCloud8x8Module.base', 'Guests must be approved by a moderator.')) ?>
            
            <div style="margin-top: 15px;">
                <?= ModalButton::submitModal(null, Yii::t('JitsiMeetCloud8x8Module.base', 'Start Stream'))
                    ->cssClass('btn btn-primary btn-block jitsi-btn-create') 
                    ->options(['style' => 'width: 100%;']) 
                ?>
            </div>
        </div>
    <?php ActiveForm::end(); ?>
<?php ModalDialog::end(); ?>

<script <?= Html::nonce() ?>>
    $('#create-stream-form').on('beforeSubmit', function(e) {
        // If "Opens in new window" is checked
        if ($('#joinroomform-newwindow').prop("checked") == true) {
            var form = $(this);
            // We need to submit this normally to open in new window, bypassing AJAX for the new window event
            // But ModalButton usually does AJAX. 
            // We might need to handle this manually or let standard form submission happen.
            // For now, let's allow standard submission which might refresh index or open tab.
            
            // To force new window from modal, we might need target="_blank" on the form
            $(this).attr('target', '_blank');
            
            // Close modal after short delay
            setTimeout(function() { $('#globalModal').modal('hide'); }, 500);
            return true;
        }
        return true;
    });
</script>

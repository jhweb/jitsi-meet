<?php

use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;
use yii\bootstrap\ActiveForm;
use humhub\libs\Html;
use humhub\modules\topic\widgets\TopicPicker;

/* @var $model \humhubContrib\modules\jitsiMeetCloud8x8\models\JoinRoomForm */
/* @var $containers array GUID => Label */
/* @var $defaultContainerGuid string|null */
?>

<?php ModalDialog::begin(['header' => Yii::t('JitsiMeetCloud8x8Module.base', 'Create Live Stream'), 'class' => 'jitsi-modal-overrides']) ?>
    <?php $form = ActiveForm::begin(['id' => 'create-stream-form', 'action' => ['index']]); ?>
        <div class="modal-body">
            <p><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Enter a name for your live stream room.') ?></p>

            <?php if (!empty($containers) && count($containers) > 1): ?>
            <div class="form-group">
                <label class="control-label"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Stream on') ?></label>
                <?= $form->field($model, 'targetContainer')->dropDownList($containers, [
                    'class' => 'form-control',
                    'options' => [$defaultContainerGuid => ['selected' => true]],
                ])->label(false) ?>
            </div>
            <?php elseif (!empty($containers)): ?>
                <?= Html::activeHiddenInput($model, 'targetContainer', ['value' => $defaultContainerGuid]) ?>
            <?php endif; ?>
            
            <?= $form->field($model, 'room')->textInput(['placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Stream Name')])->label(false) ?>

            <?= $form->field($model, 'description')->textarea([
                'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Optional description for the stream'),
                'rows' => 3,
                'maxlength' => 5000,
                'id' => 'create-stream-description',
            ])->label(Yii::t('JitsiMeetCloud8x8Module.base', 'Description')) ?>
            <div id="create-desc-word-count" class="text-right text-muted" style="margin-top: -10px; margin-bottom: 10px; font-size: 12px;">
                <span id="create-current-words">0</span> / 200 <?= Yii::t('JitsiMeetCloud8x8Module.base', 'words') ?>
            </div>

            <div class="form-group">
                <label class="control-label"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Topics') ?></label>
                <?= TopicPicker::widget([
                    'name' => 'topics',
                    'options' => ['placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Select topic...')],
                ]) ?>
            </div>

            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="is_public" value="1" checked> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Public') ?>
                    </label>
                </div>
                <p class="help-block" style="font-size: 12px; margin-top: 2px;">
                    <i class="fa fa-info-circle"></i>
                    <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Public streams are visible to all members. Private streams are only visible to space members.') ?>
                </p>
            </div>

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
            $(this).attr('target', '_blank');
            setTimeout(function() { $('#globalModal').modal('hide'); }, 500);
            return true;
        }
        return true;
    });

    // Word count for description
    $(function() {
        var maxWords = 200;
        var $textarea = $('#create-stream-description');
        var $countSpan = $('#create-current-words');
        var $countDiv = $('#create-desc-word-count');
        var $btn = $('.jitsi-modal-overrides .jitsi-btn-create');

        function countWords(str) {
            if (!str) return 0;
            var text = str.trim();
            return text.length === 0 ? 0 : text.split(/\s+/).length;
        }

        function updateWordCount() {
            var count = countWords($textarea.val());
            $countSpan.text(count);

            if (count > maxWords) {
                $countDiv.css('color', 'red').css('font-weight', 'bold');
                $btn.prop('disabled', true).addClass('disabled');
            } else {
                $countDiv.css('color', '').css('font-weight', '');
                $btn.prop('disabled', false).removeClass('disabled');
            }
        }

        $textarea.on('keyup input paste', updateWordCount);
        updateWordCount();
    });
</script>

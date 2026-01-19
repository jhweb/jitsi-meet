<?php

use humhub\libs\Html;
use humhub\widgets\Button;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;
use humhub\widgets\ModalDialog;
use humhub\modules\content\widgets\richtext\RichTextField;
use humhub\modules\topic\widgets\TopicPicker;

/* @var $model \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream */

$this->registerCss('
    .schedule-duration-slider {
        width: 100%;
        margin: 10px 0;
    }
    .duration-display {
        font-weight: bold;
        font-size: 16px;
        color: #333;
    }
');

$this->registerJs('
    $(document).ready(function() {
        var durationSlider = document.getElementById("stream-duration");
        var durationDisplay = document.getElementById("duration-display");
        var scheduledEnd = document.getElementById("jitsilivestream-scheduled_end");
        
        function updateDuration() {
            var minutes = parseInt(durationSlider.value);
            var hours = Math.floor(minutes / 60);
            var mins = minutes % 60;
            durationDisplay.textContent = (hours > 0 ? hours + "h " : "") + mins + "m";
            
            // Update end time based on start time + duration
            var startInput = document.getElementById("jitsilivestream-scheduled_start");
            if (startInput && startInput.value) {
                var startDate = new Date(startInput.value);
                startDate.setMinutes(startDate.getMinutes() + minutes);
                
                // Format as YYYY-MM-DDTHH:MM (Local Time)
                var year = startDate.getFullYear();
                var month = ("0" + (startDate.getMonth() + 1)).slice(-2);
                var day = ("0" + startDate.getDate()).slice(-2);
                var hours = ("0" + startDate.getHours()).slice(-2);
                var minutes = ("0" + startDate.getMinutes()).slice(-2);
                
                var endStr = year + "-" + month + "-" + day + "T" + hours + ":" + minutes;
                scheduledEnd.value = endStr;
            }
        }
        
        if (durationSlider) {
            durationSlider.addEventListener("input", updateDuration);
            updateDuration();
        }
    });
');
?>

<?php 
$isEdit = !$model->isNewRecord;
$actionUrl = $isEdit ? Url::to(['/jitsi-meet-cloud-8x8/room/edit', 'id' => $model->id]) : Url::to(['/jitsi-meet-cloud-8x8/room/schedule']);
$title = $isEdit ? Yii::t('JitsiMeetCloud8x8Module.base', 'Edit Stream') : Yii::t('JitsiMeetCloud8x8Module.base', 'Schedule New Stream');
$submitButtonText = $isEdit ? Yii::t('JitsiMeetCloud8x8Module.base', 'Save Changes') : Yii::t('JitsiMeetCloud8x8Module.base', 'Schedule Stream');
?>

<?php ModalDialog::begin(['header' => '<i class="fa fa-calendar-plus-o"></i> ' . $title, 'class' => 'jitsi-schedule-modal']); ?>

<?php $form = ActiveForm::begin([
    'id' => 'schedule-stream-form',
    'action' => $actionUrl,
    'enableClientValidation' => true,
]); ?>

<div class="modal-body">
    <?php if (!$isEdit): ?>
    <div class="form-group">
        <label class="control-label"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Target Calendar') ?></label>
        <?= Html::dropDownList('target_calendar', $defaultCalendarGuid, $calendars, [
            'class' => 'form-control', 
            'data-ui-select2' => '',
            'options' => isset($disabledOptions) ? $disabledOptions : []
        ]) ?>
    </div>
    <?php endif; ?>

    <?= $form->field($model, 'title')->textInput([
        'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'e.g. Weekly Bible Study'),
        'maxlength' => 255
    ])->label(Yii::t('JitsiMeetCloud8x8Module.base', 'Stream Title')) ?>

    <?= $form->field($model, 'description')->widget(RichTextField::class, [
        'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Optional description for the event'),
        'layout' => \humhub\modules\content\widgets\richtext\RichTextFieldLayout::class,
    ]) ?>
    <div id="desc-word-count" class="text-right text-muted" style="margin-top: -10px; margin-bottom: 10px; font-size: 12px;">
        <span id="current-words">0</span> / 500 <?= Yii::t('JitsiMeetCloud8x8Module.base', 'words') ?>
    </div>

    <script>
        $(function() {
            var maxWords = 500;
            var $countSpan = $('#current-words');
            var $countDiv = $('#desc-word-count');
            
            function countWords(str) {
                return str.trim().length === 0 ? 0 : str.trim().split(/\s+/).length;
            }

            function updateWordCount() {
                var $editor = $('.jitsi-schedule-modal .ProseMirror');
                if ($editor.length) {
                    var text = $editor.text();
                    var count = countWords(text);
                    $countSpan.text(count);

                    var $btn = $('.jitsi-schedule-modal .modal-footer .btn-primary');
                    
                    if (count > maxWords) {
                        $countDiv.css('color', 'red').css('font-weight', 'bold');
                        $btn.prop('disabled', true);
                        $btn.attr('title', 'Description exceeds word limit');
                    } else {
                        $countDiv.css('color', '').css('font-weight', '');
                        $btn.prop('disabled', false);
                        $btn.attr('title', '');
                    }
                }
            }
            
            // Monitor changes
            $('body').on('keyup input paste', '.jitsi-schedule-modal .ProseMirror', function() {
                updateWordCount();
            });
            
            // Initial check (delay to let editor load)
            setTimeout(updateWordCount, 500);
        });
    </script>

    <?php if (!empty($types)): ?>
    <div class="form-group">
        <label class="control-label"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Event Type') ?></label>
        <?= Html::dropDownList('type_id', null, $types, ['class' => 'form-control', 'prompt' => Yii::t('JitsiMeetCloud8x8Module.base', 'Select event type...')]) ?>
    </div>
    <?php endif; ?>

    <div class="form-group">
        <label class="control-label"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Topics') ?></label>
        <?= TopicPicker::widget([
            'name' => 'topics',
            'options' => ['placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Select topic...')],
        ]) ?>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'scheduled_start')->input('datetime-local', [
                'class' => 'form-control',
                'required' => true
            ])->label(Yii::t('JitsiMeetCloud8x8Module.base', 'Start Date & Time')) ?>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="control-label"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Duration') ?></label>
                <input type="range" id="stream-duration" class="schedule-duration-slider" 
                       min="15" max="240" step="15" value="60">
                <div class="text-center duration-display" id="duration-display">1h 0m</div>
            </div>
        </div>
    </div>

    <?= $form->field($model, 'scheduled_end')->hiddenInput()->label(false) ?>

    <?= $form->field($model, 'all_day')->checkbox()->label(Yii::t('JitsiMeetCloud8x8Module.base', 'All day event')) ?>
    
    <div class="form-group">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="is_public" value="1" checked> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Public') ?>
            </label>
        </div>
    </div>
    
    <?= $form->field($model, 'lobby_enabled')->checkbox()->label(Yii::t('JitsiMeetCloud8x8Module.base', 'Enable Waiting Room (Lobby)')) ?>

    <div class="form-group">
        <label class="control-label"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Timezone') ?></label>
        <?= Html::dropDownList('JitsiLiveStream[timezone]', $model->timezone ?: date_default_timezone_get(), [
            'UTC' => 'UTC',
            'Africa/Johannesburg' => 'Africa/Johannesburg (SAST)',
            'Europe/London' => 'Europe/London (GMT/BST)',
            'America/New_York' => 'America/New York (EST/EDT)',
            'America/Los_Angeles' => 'America/Los Angeles (PST/PDT)',
            'Asia/Tokyo' => 'Asia/Tokyo (JST)',
        ], ['class' => 'form-control']) ?>
    </div>

    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        <?= Yii::t('JitsiMeetCloud8x8Module.base', 'A Calendar Entry will be automatically created. If Lobby is enabled, guests must be approved by a moderator.') ?>
    </div>
</div>

<div class="modal-footer">
    <?= Button::defaultType(Yii::t('JitsiMeetCloud8x8Module.base', 'Cancel'))
        ->options(['data-dismiss' => 'modal']) ?>
    <?= Button::primary($submitButtonText)
        ->submit() ?>
</div>

<?php ActiveForm::end(); ?>
<?php ModalDialog::end(); ?>

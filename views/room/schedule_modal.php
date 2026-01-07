<?php

use humhub\libs\Html;
use humhub\widgets\Button;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;
use humhub\widgets\ModalDialog;

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
                // Format as YYYY-MM-DD HH:MM
                var endStr = startDate.toISOString().slice(0, 16);
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

<?php ModalDialog::begin(['header' => Yii::t('JitsiMeetCloud8x8Module.base', 'Schedule New Stream'), 'icon' => 'fa-calendar-plus-o']); ?>

<?php $form = ActiveForm::begin([
    'id' => 'schedule-stream-form',
    'action' => Url::to(['/jitsi-meet-cloud-8x8/room/schedule']),
    'enableClientValidation' => true,
]); ?>

<div class="modal-body">
    <?= $form->field($model, 'title')->textInput([
        'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'e.g. Weekly Bible Study'),
        'maxlength' => 255
    ])->label(Yii::t('JitsiMeetCloud8x8Module.base', 'Stream Title')) ?>

    <?= $form->field($model, 'description')->textarea([
        'rows' => 3,
        'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'Optional description for the event')
    ]) ?>

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
        <?= Yii::t('JitsiMeetCloud8x8Module.base', 'When the scheduled time arrives, the stream will automatically appear as "Live" and participants can join.') ?>
    </div>
</div>

<div class="modal-footer">
    <?= Button::defaultType(Yii::t('JitsiMeetCloud8x8Module.base', 'Cancel'))
        ->options(['data-dismiss' => 'modal']) ?>
    <?= Button::primary(Yii::t('JitsiMeetCloud8x8Module.base', 'Schedule Stream'))
        ->submit() ?>
</div>

<?php ActiveForm::end(); ?>
<?php ModalDialog::end(); ?>

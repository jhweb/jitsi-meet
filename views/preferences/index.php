<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;

/** @var \humhubContrib\modules\jitsiMeetCloud8x8\models\UserNotificationPreference $model */
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="fa fa-bell"></i>
            <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Video Chat Notification Preferences') ?>
        </h3>
    </div>
    
    <div class="panel-body">
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i>
            <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Configure when you want to be notified about scheduled video chats.') ?>
        </div>

        <?php $form = ActiveForm::begin([
            'id' => 'notification-preferences-form',
            'options' => ['class' => 'form-horizontal'],
            'fieldConfig' => [
                'template' => "{label}\n<div class=\"col-sm-9\">{input}\n{error}</div>",
                'labelOptions' => ['class' => 'col-sm-3 control-label'],
            ],
        ]); ?>

        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
                <h4><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Standard Notifications') ?></h4>
            </div>
        </div>

        <?= $form->field($model, 'notify_24h_before')->checkbox([
            'label' => Yii::t('JitsiMeetCloud8x8Module.base', 'Notify 24 hours before scheduled video chats'),
            'labelOptions' => ['class' => 'control-label']
        ]) ?>

        <?= $form->field($model, 'notify_5min_before')->checkbox([
            'label' => Yii::t('JitsiMeetCloud8x8Module.base', 'Notify 5 minutes before scheduled video chats'),
            'labelOptions' => ['class' => 'control-label']
        ]) ?>

        <?= $form->field($model, 'notify_on_start')->checkbox([
            'label' => Yii::t('JitsiMeetCloud8x8Module.base', 'Notify when scheduled video chat starts'),
            'labelOptions' => ['class' => 'control-label']
        ]) ?>

        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
                <h4><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Custom Notifications') ?></h4>
                <p class="help-block">
                    <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Enter custom notification intervals in minutes (comma-separated). For example: 60, 30, 10') ?>
                </p>
            </div>
        </div>

        <?= $form->field($model, 'custom_intervals')->textInput([
            'placeholder' => Yii::t('JitsiMeetCloud8x8Module.base', 'e.g., 60, 30, 10'),
            'class' => 'form-control'
        ]) ?>

        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
                <div class="alert alert-warning">
                    <i class="fa fa-warning"></i>
                    <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Custom intervals must be between 1 and 10080 minutes (1 week).') ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
                <h4><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Current Settings Summary') ?></h4>
                <div id="settings-summary" class="well">
                    <p><strong><?= Yii::t('JitsiMeetCloud8x8Module.base', 'You will be notified:') ?></strong></p>
                    <ul id="intervals-list">
                        <!-- Will be populated by JavaScript -->
                    </ul>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-sm-offset-3 col-sm-9">
                <?= Html::submitButton(Yii::t('JitsiMeetCloud8x8Module.base', 'Save Preferences'), [
                    'class' => 'btn btn-primary',
                    'id' => 'save-preferences-btn'
                ]) ?>
                
                <?= Html::button(Yii::t('JitsiMeetCloud8x8Module.base', 'Reset to Defaults'), [
                    'class' => 'btn btn-default',
                    'id' => 'reset-preferences-btn'
                ]) ?>
                
                <a href="<?= Url::to(['/user/account']) ?>" class="btn btn-link">
                    <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Back to Account Settings') ?>
                </a>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Update settings summary when form changes
    function updateSettingsSummary() {
        var intervals = [];
        
        if ($('#usernotificationpreference-notify_24h_before').is(':checked')) {
            intervals.push('<?= Yii::t('JitsiMeetCloud8x8Module.base', '24 hours before') ?>');
        }
        
        if ($('#usernotificationpreference-notify_5min_before').is(':checked')) {
            intervals.push('<?= Yii::t('JitsiMeetCloud8x8Module.base', '5 minutes before') ?>');
        }
        
        if ($('#usernotificationpreference-notify_on_start').is(':checked')) {
            intervals.push('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'when the chat starts') ?>');
        }
        
        var customIntervals = $('#usernotificationpreference-custom_intervals').val();
        if (customIntervals.trim()) {
            var customParts = customIntervals.split(',');
            customParts.forEach(function(interval) {
                interval = interval.trim();
                if (interval && !isNaN(interval)) {
                    var minutes = parseInt(interval);
                    if (minutes >= 60) {
                        var hours = Math.floor(minutes / 60);
                        var remainingMinutes = minutes % 60;
                        var timeStr = hours + ' ' + (hours === 1 ? '<?= Yii::t('JitsiMeetCloud8x8Module.base', 'hour') ?>' : '<?= Yii::t('JitsiMeetCloud8x8Module.base', 'hours') ?>');
                        if (remainingMinutes > 0) {
                            timeStr += ' ' + remainingMinutes + ' ' + (remainingMinutes === 1 ? '<?= Yii::t('JitsiMeetCloud8x8Module.base', 'minute') ?>' : '<?= Yii::t('JitsiMeetCloud8x8Module.base', 'minutes') ?>');
                        }
                        intervals.push(timeStr + ' <?= Yii::t('JitsiMeetCloud8x8Module.base', 'before') ?>');
                    } else {
                        intervals.push(minutes + ' ' + (minutes === 1 ? '<?= Yii::t('JitsiMeetCloud8x8Module.base', 'minute') ?>' : '<?= Yii::t('JitsiMeetCloud8x8Module.base', 'minutes') ?>') + ' <?= Yii::t('JitsiMeetCloud8x8Module.base', 'before') ?>');
                    }
                }
            });
        }
        
        var listHtml = '';
        if (intervals.length === 0) {
            listHtml = '<li class="text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'No notifications enabled') ?></li>';
        } else {
            intervals.forEach(function(interval) {
                listHtml += '<li>' + interval + '</li>';
            });
        }
        
        $('#intervals-list').html(listHtml);
    }
    
    // Update summary on form changes
    $('#notification-preferences-form input, #notification-preferences-form textarea').on('change keyup', updateSettingsSummary);
    
    // Initial update
    updateSettingsSummary();
    
    // Handle reset button
    $('#reset-preferences-btn').on('click', function() {
        if (confirm('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Are you sure you want to reset your notification preferences to defaults?') ?>')) {
            $.ajax({
                url: '<?= Url::to(['/jitsi-meet-cloud-8x8/preferences/reset']) ?>',
                method: 'POST',
                data: {
                    <?= Yii::$app->request->csrfParam ?>: '<?= Yii::$app->request->csrfToken ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Reset form to defaults
                        $('#usernotificationpreference-notify_24h_before').prop('checked', true);
                        $('#usernotificationpreference-notify_5min_before').prop('checked', true);
                        $('#usernotificationpreference-notify_on_start').prop('checked', false);
                        $('#usernotificationpreference-custom_intervals').val('');
                        
                        updateSettingsSummary();
                        humhub.modules.ui.status.success(response.message);
                    } else {
                        humhub.modules.ui.status.error(response.message);
                    }
                },
                error: function() {
                    humhub.modules.ui.status.error('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Failed to reset preferences') ?>');
                }
            });
        }
    });
    
    // Handle form submission
    $('#notification-preferences-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: '<?= Url::to(['/jitsi-meet-cloud-8x8/preferences/save']) ?>',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    humhub.modules.ui.status.success(response.message);
                } else {
                    humhub.modules.ui.status.error(response.message);
                }
            },
            error: function() {
                humhub.modules.ui.status.error('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Failed to save preferences') ?>');
            }
        });
    });
});
</script>

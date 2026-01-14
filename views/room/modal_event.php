<?php

use humhub\libs\Html;
use humhub\widgets\ModalDialog;
use humhub\widgets\Button;
use humhub\modules\user\widgets\Image;
use yii\helpers\Url;

/* @var $stream \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream */
/* @var $calendarEntry \humhub\modules\calendar\models\CalendarEntry */
/* @var $isAttending bool */
/* @var $attendees array User models of attendees */
/* @var $attendeeCount int Total attendee count */

$isCreator = (!Yii::$app->user->isGuest && $stream->creator_id == Yii::$app->user->id);
?>

<?php ModalDialog::begin(['header' => '<i class="fa fa-calendar"></i> ' . Html::encode($calendarEntry->title), 'class' => 'jitsi-modal-overrides']); ?>

<div class="modal-body">
    <div class="event-details">
        <div class="row">
            <div class="col-md-12">
                
                <!-- Countdown Timer -->
                <?php if ($stream->status == \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream::STATUS_SCHEDULED): ?>
                <div class="countdown-display" style="text-align: center; font-size: 18px; font-weight: bold; color: #4a90d9; margin-bottom: 15px; padding: 10px; background: rgba(74, 144, 217, 0.1); border-radius: 4px;">
                    <i class="fa fa-hourglass-half"></i>
                    <?= $stream->getCountdown() ?>
                </div>
                <?php endif; ?>

                <p>
                    <i class="fa fa-clock-o"></i>
                    <strong><?= Yii::$app->formatter->asDatetime($calendarEntry->start_datetime, 'medium') ?></strong>
                    <?php if ($calendarEntry->end_datetime): ?>
                        - <?= Yii::$app->formatter->asDatetime($calendarEntry->end_datetime, 'medium') ?>
                    <?php endif; ?>
                </p>
                
                <?php if (!empty($calendarEntry->description)): ?>
                <div class="event-description" style="margin: 15px 0; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                    <?= Html::encode($calendarEntry->description) ?>
                </div>
                <?php endif; ?>

                <?php if ($stream->creator): ?>
                <p>
                    <i class="fa fa-user"></i>
                    <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Hosted by') ?>: 
                    <a href="<?= $stream->creator->getUrl() ?>"><?= Html::encode($stream->creator->displayName) ?></a>
                </p>
                <?php endif; ?>

                <hr>

                <!-- Attendees Section -->
                <div class="attendees-section" style="margin: 15px 0;">
                    <h5><i class="fa fa-users"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Attendees') ?> (<?= $attendeeCount ?>)</h5>
                    
                    <?php if (!empty($attendees)): ?>
                    <div class="attendee-icons" style="display: flex; flex-wrap: wrap; gap: 5px; margin-top: 10px;">
                        <?php foreach ($attendees as $attendee): ?>
                            <a href="<?= $attendee->getUrl() ?>" title="<?= Html::encode($attendee->displayName) ?>" style="text-decoration: none;">
                                <?= Image::widget(['user' => $attendee, 'width' => 32, 'showTooltip' => true, 'link' => false]) ?>
                            </a>
                        <?php endforeach; ?>
                        <?php if ($attendeeCount > 20): ?>
                            <span class="more-attendees" style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: #ddd; border-radius: 50%; font-size: 11px; color: #666;">
                                +<?= $attendeeCount - 20 ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted" style="font-style: italic;"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'No attendees yet. Be the first to RSVP!') ?></p>
                    <?php endif; ?>
                </div>

                <hr>

                <div class="participation-section">
                    <h5><i class="fa fa-hand-paper-o"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Your Response') ?></h5>
                    
                    <?php if (Yii::$app->user->isGuest): ?>
                        <p class="text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Please log in to RSVP.') ?></p>
                    <?php elseif ($isCreator): ?>
                        <p class="text-success"><i class="fa fa-check"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'You are the host of this event.') ?></p>
                    <?php else: ?>
                        <div class="btn-group" style="margin: 10px 0;">
                            <?php
                            $attendUrl = Url::to(['/jitsi-meet-cloud-8x8/room/attend', 'id' => $stream->id, 'type' => \humhub\modules\calendar\models\CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED]);
                            $declineUrl = Url::to(['/jitsi-meet-cloud-8x8/room/attend', 'id' => $stream->id, 'type' => \humhub\modules\calendar\models\CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED]);
                            ?>
                            
                            <?php if ($isAttending): ?>
                                <span class="btn btn-success disabled"><i class="fa fa-check"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Attending') ?></span>
                                <?= Html::a('<i class="fa fa-times"></i> ' . Yii::t('JitsiMeetCloud8x8Module.base', 'Cancel'), $declineUrl, [
                                    'class' => 'btn btn-default',
                                    'data-method' => 'post',
                                ]) ?>
                            <?php else: ?>
                                <?= Html::a('<i class="fa fa-check"></i> ' . Yii::t('JitsiMeetCloud8x8Module.base', 'Attend'), $attendUrl, [
                                    'class' => 'btn btn-primary',
                                    'data-method' => 'post',
                                ]) ?>
                            <?php endif; ?>
                        </div>

                        
                        <!-- Reminder Checkbox -->
                        <div style="display: inline-block; margin-left: 15px; vertical-align: middle;">
                             <label style="cursor: pointer; font-weight: normal; margin: 0;">
                                <input type="checkbox" id="jitsi-reminder-cb" <?= (isset($hasReminder) && $hasReminder) ? 'checked' : '' ?> onchange="toggleJitsiReminder(this, <?= $stream->id ?>)">
                                <span style="margin-left: 5px;"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Remind me (33m & 1d before)') ?></span>
                            </label>
                            
                            <script>
                            function toggleJitsiReminder(cb, streamId) {
                                var url = "<?= Url::to(['/jitsi-meet-cloud-8x8/room/toggle-reminder']) ?>";
                                url += "?id=" + streamId;
                                
                                $(cb).prop('disabled', true);
                                
                                $.ajax({
                                    url: url,
                                    type: 'POST',
                                    dataType: 'json',
                                    success: function(data) {
                                        $(cb).prop('disabled', false);
                                        if(data.success) {
                                            $(cb).prop('checked', data.reminder);
                                            humhub.modules.ui.status.success('<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Reminder updated') ?>');
                                        } else {
                                            humhub.modules.ui.status.error('Error updating reminder');
                                        }
                                    },
                                    error: function() {
                                         $(cb).prop('disabled', false);
                                         humhub.modules.ui.status.error('Error updating reminder');
                                    }
                                });
                            }
                            </script>
                        </div>
                    <?php endif; ?>
                </div>

                <hr>

                <div class="join-section" style="text-align: center; margin-top: 20px; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                    <?php
                    $canManage = false; 
                    if (!Yii::$app->user->isGuest) {
                        if ($isCreator) {
                            $canManage = true;
                        } elseif (Yii::$app->user->isAdmin()) {
                            $canManage = true;
                        } elseif ($stream->space && $stream->space->isAdmin()) {
                            $canManage = true;
                        }
                    }
                    
                    if ($canManage): ?>
                        <a href="#" data-action-click="ui.modal.load" data-action-url="<?= Url::to(['/jitsi-meet-cloud-8x8/room/edit', 'id' => $stream->id]) ?>" class="btn btn-warning btn-lg">
                            <?= Yii::t('JitsiMeetCloud8x8Module.base', 'EDIT') ?>
                        </a>
                    <?php endif; ?>

                    <?php
                    $hideJoin = false;
                    $timeMsg = '';
                    // 30-minute Access Rule
                    if ($stream->scheduled_start && strtotime($stream->scheduled_start) > (time() + 1800)) {
                        if (!$canManage) {
                            $hideJoin = true;
                            $diff = strtotime($stream->scheduled_start) - time();
                            // Simple human readable format or just standard format
                            $timeMsg = Yii::t('JitsiMeetCloud8x8Module.base', 'Starts in {0}', [Yii::$app->formatter->asDuration($diff)]);
                        }
                    }
                    ?>

                    <?php if (!$hideJoin): ?>
                    <a href="<?= Url::to(['/jitsi-meet-cloud-8x8/room/open', 'name' => $stream->room_name]) ?>" 
                       class="btn btn-lg btn-success" style="background-color: #5cb85c; border-color: #4cae4c; color: white;" target="_blank">
                        <i class="fa fa-video-camera"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Join Watch Room') ?>
                    </a>
                    <?php else: ?>
                    <button class="btn btn-lg btn-default disabled" disabled>
                        <i class="fa fa-clock-o"></i> <?= $timeMsg ?>
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($canManage): ?>
                        <?= Html::a(Yii::t('JitsiMeetCloud8x8Module.base', 'DELETE'), Url::to(['/jitsi-meet-cloud-8x8/room/delete', 'id' => $stream->id]), [
                            'class' => 'btn btn-danger btn-lg',
                            'data-method' => 'post',
                            'data-confirm' => Yii::t('JitsiMeetCloud8x8Module.base', 'Are you sure you want to delete this event?'),
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <?= Button::defaultType(Yii::t('JitsiMeetCloud8x8Module.base', 'Close'))
        ->options(['data-dismiss' => 'modal']) ?>
</div>

<?php ModalDialog::end(); ?>

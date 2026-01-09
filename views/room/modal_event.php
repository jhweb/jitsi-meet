<?php

use humhub\libs\Html;
use humhub\widgets\ModalDialog;
use humhub\widgets\Button;
use yii\helpers\Url;

/* @var $stream \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream */
/* @var $calendarEntry \humhub\modules\calendar\models\CalendarEntry */
/* @var $isAttending bool */

$isCreator = (!Yii::$app->user->isGuest && $stream->creator_id == Yii::$app->user->id);
?>

<?php ModalDialog::begin(['header' => '<i class="fa fa-calendar"></i> ' . Html::encode($calendarEntry->title)]); ?>

<div class="modal-body">
    <div class="event-details">
        <div class="row">
            <div class="col-md-12">
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

                <div class="participation-section">
                    <h5><i class="fa fa-users"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Your Response') ?></h5>
                    
                    <?php if (Yii::$app->user->isGuest): ?>
                        <p class="text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Please log in to RSVP.') ?></p>
                    <?php elseif ($isCreator): ?>
                        <p class="text-success"><i class="fa fa-check"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'You are the host of this event.') ?></p>
                    <?php else: ?>
                        <div class="btn-group" style="margin: 10px 0;">
                            <?php
                            $attendUrl = Url::to(['/jitsi-meet-cloud-8x8/room/attend', 'id' => $stream->id, 'type' => 2]);
                            $declineUrl = Url::to(['/jitsi-meet-cloud-8x8/room/attend', 'id' => $stream->id, 'type' => 4]);
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
                    <?php endif; ?>
                </div>

                <hr>

                <div class="join-section" style="text-align: center; margin-top: 20px;">
                    <a href="<?= Url::to(['/jitsi-meet-cloud-8x8/room/open', 'name' => $stream->room_name]) ?>" 
                       class="btn btn-lg btn-primary" target="_blank">
                        <i class="fa fa-video-camera"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Join Watch Room') ?>
                    </a>
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

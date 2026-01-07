<?php

use humhub\libs\Html;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use humhub\modules\user\widgets\Image;

/* @var $model \humhubContrib\modules\jitsiMeetCloud8x8\models\JoinRoomForm */
/* @var $scheduledStreams \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream[] */
/* @var $activeStreams \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream[] */
/* @var $endedStreams \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream[] */
/* @var $canSchedule bool */

$assets = \humhubContrib\modules\jitsiMeetCloud8x8\assets\Assets::register($this);
?>
<div class="container">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Open conference room'); ?>
                
                <?php if (!empty($canSchedule)): ?>
                <?= ModalButton::primary('<i class="fa fa-calendar-plus-o"></i> ' . Yii::t('JitsiMeetCloud8x8Module.base', 'Schedule Stream'))
                    ->load(Url::to(['schedule']))
                    ->cssClass('btn btn-sm btn-primary pull-right')
                    ->style('margin-left: 10px;') ?>
                <?php endif; ?>
                
                <?php if (Yii::$app->getModule('jitsi-meet-cloud-8x8')->settings->get('enableTour', 1)): ?>
                <button id="jitsi-guide-button" class="btn btn-xs btn-info pull-right">
                    <i class="fa fa-question-circle"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Guide'); ?>
                </button>
                <?php endif; ?>
            </div>
            <div class="panel-body" id="jitsi-join-panel">
                <?php $form = ActiveForm::begin(['layout' => 'horizontal', 'id' => 'jrform']); ?>

                <?= $form->field($model, 'room'); ?>
                <?= $form->field($model, 'newWindow')->checkbox(); ?>
                
                <?= Button::save(Yii::t('JitsiMeetCloud8x8Module.base', 'Join'))->loader(false)->submit() ?>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
        
        <!-- Live Streams Grid -->
        <div class="live-stream-grid">
                <?php 
                // Merge streams for a single grid flow: scheduled → active → ended
                $allStreams = array_merge(
                    $scheduledStreams ?? [], 
                    $activeStreams, 
                    $endedStreams
                );
                ?>
                
                <?php foreach ($allStreams as $stream): ?>
                    <?= $this->render('_stream_card', ['stream' => $stream]) ?>
                <?php endforeach; ?>

                
                <!-- Placeholders to fill grid if few items -->
                <?php for($i=0; $i < max(0, 4 - count($allStreams)); $i++): ?>
                 <div class="stream-card ended" style="opacity: 0.3; border-style: dashed;">
                 </div>
                <?php endfor; ?>

            </div>
            
            <div class="pagination-container" style="text-align: center; width: 100%;">
                <?= LinkPager::widget([
                    'pagination' => $pages,
                    'options' => ['class' => 'pagination', 'style' => 'display: inline-block;'],
                    'maxButtonCount' => 5,
                ]); ?>
            </div>
        </div>
        
    </div>
</div>

<?= \humhubContrib\modules\jitsiMeetCloud8x8\widgets\StreamGuide::widget() ?>

<script <?= Html::nonce() ?>>
    $('#jrform').on('beforeSubmit', function(e) {
        if ($('#joinroomform-newwindow'). prop("checked") == true) {
            $('#jrform').attr('target','_blank');
        }
        return true;
    });
</script>


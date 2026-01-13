<?php

use humhub\libs\Html;
use humhub\widgets\Button;
use humhub\widgets\ModalButton;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use humhub\modules\user\widgets\Image;
use yii\widgets\Pjax;

/* @var $model \humhubContrib\modules\jitsiMeetCloud8x8\models\JoinRoomForm */
/* @var $scheduledStreams \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream[] */
/* @var $activeStreams \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream[] */
/* @var $endedStreams \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream[] */
/* @var $canSchedule bool */
/* @var $pages \yii\data\Pagination */

$assets = \humhubContrib\modules\jitsiMeetCloud8x8\assets\Assets::register($this);

$filter = Yii::$app->request->get('filter', 'all');
?>

<div class="jitsi-layout-container">
    
    <?php Pjax::begin(['id' => 'jitsi-stream-grid-pjax']); ?>

    <!-- Filter & Action Bar -->
    <div class="jitsi-filter-bar">
        <div class="jitsi-filters">
            <a href="<?= Url::to(['index', 'filter' => 'all']) ?>" class="jitsi-filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                <i class="fa fa-th-large"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'All Streams') ?>
            </a>
            <a href="<?= Url::to(['index', 'filter' => 'live']) ?>" class="jitsi-filter-btn <?= $filter === 'live' ? 'active' : '' ?>">
                <i class="fa fa-dot-circle-o" style="<?= $filter === 'live' ? '' : 'color: var(--jitsi-live);' ?>"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Live Now') ?>
            </a>
            <a href="<?= Url::to(['index', 'filter' => 'scheduled']) ?>" class="jitsi-filter-btn <?= $filter === 'scheduled' ? 'active' : '' ?>">
                <i class="fa fa-calendar"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Scheduled') ?>
            </a>
            <a href="<?= Url::to(['index', 'filter' => 'ended']) ?>" class="jitsi-filter-btn <?= $filter === 'ended' ? 'active' : '' ?>">
                <i class="fa fa-history"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Ended') ?>
            </a>
        </div>

        <div class="jitsi-actions">
            <?php if ($canSchedule): ?>
                <?= ModalButton::primary('<i class="fa fa-calendar-plus-o"></i> ' . Yii::t('JitsiMeetCloud8x8Module.base', 'Schedule Stream'))
                    ->load(Url::to(['schedule']))
                    ->cssClass('jitsi-action-btn jitsi-btn-schedule') ?>
            <?php endif; ?>

            <?= ModalButton::success('<i class="fa fa-plus-circle"></i> ' . Yii::t('JitsiMeetCloud8x8Module.base', 'Create Stream'))
                ->load(Url::to(['create']))
                ->cssClass('jitsi-action-btn jitsi-btn-create') ?>
        </div>
    </div>

    <!-- Stats / Info (Optional, skipping for now based on mockup) -->

    <!-- Live Streams Grid -->
    <div class="live-stream-grid">
        <?php 
        // Filter Logic in View
        $streamsToShow = [];
        
        if ($filter === 'all') {
            // Merge streams order: Active -> Scheduled -> Ended
            // Note: Controller returns Active separate from Scheduled, but logical display might be Live -> Scheduled -> Ended
            // Important: We need to respect the controller's pagination for 'endedStreams'.
            $streamsToShow = array_merge(
                $activeStreams,
                $scheduledStreams,
                $endedStreams
            );
        } elseif ($filter === 'live') {
            $streamsToShow = $activeStreams;
        } elseif ($filter === 'scheduled') {
            $streamsToShow = $scheduledStreams;
        } elseif ($filter === 'ended') {
            $streamsToShow = $endedStreams; // This is paginated from controller
        }

        if (empty($streamsToShow)): ?>
            <div class="col-md-12 text-center text-muted" style="grid-column: 1 / -1; padding: 40px;">
                <i class="fa fa-film fa-3x" style="opacity: 0.3; margin-bottom: 20px;"></i><br>
                <?= Yii::t('JitsiMeetCloud8x8Module.base', 'No streams found.') ?>
            </div>
        <?php else: ?>
            <?php foreach ($streamsToShow as $stream): ?>
                <?= $this->render('_stream_card', ['stream' => $stream]) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
        
    <!-- Pagination (Only shows if there are pages, usually tied to Ended streams) -->
    <?php if ($pages->pageCount > 1 && ($filter === 'all' || $filter === 'ended')): ?>
    <div class="jitsi-pagination">
        <?= LinkPager::widget([
            'pagination' => $pages,
            'options' => ['class' => 'pagination'],
            'maxButtonCount' => 5,
        ]); ?>
    </div>
    <?php endif; ?>

    <?php Pjax::end(); ?>

</div>

<?= \humhubContrib\modules\jitsiMeetCloud8x8\widgets\StreamGuide::widget() ?>

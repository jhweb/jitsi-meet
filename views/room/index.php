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
/* @var $spaceFilterList array */
/* @var $filterSpaceId string|null */
/* @var $filterCreatorId string|null */

$assets = \humhubContrib\modules\jitsiMeetCloud8x8\assets\Assets::register($this);

$filter = Yii::$app->request->get('filter', 'all');
?>

<div class="jitsi-layout-container">
    
    <?php Pjax::begin(['id' => 'jitsi-stream-grid-pjax']); ?>

    <!-- Filter & Action Bar -->
    <div class="jitsi-filter-bar">
        <div class="jitsi-filters">
            <a href="<?= Url::to(['index', 'filter' => 'all', 'space_id' => $filterSpaceId, 'creator_id' => $filterCreatorId]) ?>" class="jitsi-filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                <i class="fa fa-th-large"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'All Streams') ?>
            </a>
            <a href="<?= Url::to(['index', 'filter' => 'live', 'space_id' => $filterSpaceId, 'creator_id' => $filterCreatorId]) ?>" class="jitsi-filter-btn <?= $filter === 'live' ? 'active' : '' ?>">
                <i class="fa fa-dot-circle-o" style="<?= $filter === 'live' ? '' : 'color: var(--jitsi-live);' ?>"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Live Now') ?>
            </a>
            <a href="<?= Url::to(['index', 'filter' => 'scheduled', 'space_id' => $filterSpaceId, 'creator_id' => $filterCreatorId]) ?>" class="jitsi-filter-btn <?= $filter === 'scheduled' ? 'active' : '' ?>">
                <i class="fa fa-calendar"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Scheduled') ?>
            </a>
            <a href="<?= Url::to(['index', 'filter' => 'ended', 'space_id' => $filterSpaceId, 'creator_id' => $filterCreatorId]) ?>" class="jitsi-filter-btn <?= $filter === 'ended' ? 'active' : '' ?>">
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

    <!-- Space & Member Filter Bar -->
    <?php if (!empty($spaceFilterList) || !Yii::$app->user->isGuest): ?>
    <div class="jitsi-secondary-filters" style="display: flex; gap: 10px; padding: 8px 0; flex-wrap: wrap; align-items: center;">
        <div style="display: flex; align-items: center; gap: 5px;">
            <i class="fa fa-filter" style="color: #888; font-size: 12px;"></i>
        </div>
        <?php if (!empty($spaceFilterList)): ?>
        <div>
            <select id="jitsi-space-filter" class="form-control input-sm" style="min-width: 150px; font-size: 12px;">
                <option value=""><?= Yii::t('JitsiMeetCloud8x8Module.base', 'All Spaces') ?></option>
                <option value="profile" <?= $filterSpaceId === 'profile' ? 'selected' : '' ?>><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Profile Only') ?></option>
                <?php foreach ($spaceFilterList as $spId => $spName): ?>
                    <option value="<?= $spId ?>" <?= $filterSpaceId == $spId ? 'selected' : '' ?>><?= Html::encode($spName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div>
            <select id="jitsi-member-filter" class="form-control input-sm" style="min-width: 150px; font-size: 12px;">
                <option value=""><?= Yii::t('JitsiMeetCloud8x8Module.base', 'All Members') ?></option>
                <?php if (!Yii::$app->user->isGuest): ?>
                    <option value="<?= Yii::$app->user->id ?>" <?= $filterCreatorId == Yii::$app->user->id ? 'selected' : '' ?>><?= Yii::t('JitsiMeetCloud8x8Module.base', 'My Streams') ?></option>
                <?php endif; ?>
            </select>
        </div>
        <?php if (!empty($filterSpaceId) || !empty($filterCreatorId)): ?>
            <a href="<?= Url::to(['index', 'filter' => $filter]) ?>" class="btn btn-xs btn-default" style="font-size: 11px;">
                <i class="fa fa-times"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Clear Filters') ?>
            </a>
        <?php endif; ?>
    </div>
    <script <?= Html::nonce() ?>>
        (function() {
            function applyFilters() {
                var spaceId = document.getElementById('jitsi-space-filter') ? document.getElementById('jitsi-space-filter').value : '';
                var creatorId = document.getElementById('jitsi-member-filter') ? document.getElementById('jitsi-member-filter').value : '';
                var params = new URLSearchParams(window.location.search);
                
                if (spaceId) { params.set('space_id', spaceId); } else { params.delete('space_id'); }
                if (creatorId) { params.set('creator_id', creatorId); } else { params.delete('creator_id'); }
                params.set('filter', '<?= Html::encode($filter) ?>');
                params.delete('page'); // Reset pagination on filter change
                
                window.location.href = window.location.pathname + '?' + params.toString();
            }
            
            var spaceSelect = document.getElementById('jitsi-space-filter');
            var memberSelect = document.getElementById('jitsi-member-filter');
            if (spaceSelect) spaceSelect.addEventListener('change', applyFilters);
            if (memberSelect) memberSelect.addEventListener('change', applyFilters);
        })();
    </script>
    <?php endif; ?>

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

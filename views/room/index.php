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
            <a href="<?= Url::to(['index', 'filter' => 'all', 'space_id' => $filterSpaceId, 'creator_id' => $filterCreatorId]) ?>" class="jitsi-filter-btn <?= $filter === 'all' ? 'active' : '' ?>"<?= $filter === 'all' ? ' aria-current="page"' : '' ?>>
                <i class="fa fa-th-large"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'All Streams') ?>
            </a>
            <a href="<?= Url::to(['index', 'filter' => 'live', 'space_id' => $filterSpaceId, 'creator_id' => $filterCreatorId]) ?>" class="jitsi-filter-btn <?= $filter === 'live' ? 'active' : '' ?>"<?= $filter === 'live' ? ' aria-current="page"' : '' ?>>
                <i class="fa fa-dot-circle-o" style="<?= $filter === 'live' ? '' : 'color: var(--danger);' ?>" aria-hidden="true"></i>
                <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Live Now') ?>
                <?php if (!empty($activeStreams)): ?>
                    <span class="jitsi-sr-only"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Live streams available') ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= Url::to(['index', 'filter' => 'scheduled', 'space_id' => $filterSpaceId, 'creator_id' => $filterCreatorId]) ?>" class="jitsi-filter-btn <?= $filter === 'scheduled' ? 'active' : '' ?>"<?= $filter === 'scheduled' ? ' aria-current="page"' : '' ?>>
                <i class="fa fa-calendar"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Scheduled') ?>
            </a>
            <a href="<?= Url::to(['index', 'filter' => 'ended', 'space_id' => $filterSpaceId, 'creator_id' => $filterCreatorId]) ?>" class="jitsi-filter-btn <?= $filter === 'ended' ? 'active' : '' ?>"<?= $filter === 'ended' ? ' aria-current="page"' : '' ?>>
                <i class="fa fa-history"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Ended') ?>
            </a>
            <a href="#" id="jitsi-guide-button" class="jitsi-filter-btn" title="<?= Html::encode(Yii::t('JitsiMeetCloud8x8Module.base', 'Restart tour')) ?>">
                <i class="fa fa-question-circle"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Guide') ?>
            </a>
        </div>

        <?php if (!empty($spaceFilterList) || !Yii::$app->user->isGuest): ?>
        <div class="jitsi-dropdown-filters">
            <?php if (!empty($spaceFilterList)): ?>
            <select id="jitsi-space-filter" class="jitsi-dropdown-select">
                <option value=""><?= Yii::t('JitsiMeetCloud8x8Module.base', 'All Spaces') ?></option>
                <option value="profile" <?= $filterSpaceId === 'profile' ? 'selected' : '' ?>><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Profile Only') ?></option>
                <?php foreach ($spaceFilterList as $spId => $spName): ?>
                    <option value="<?= $spId ?>" <?= $filterSpaceId == $spId ? 'selected' : '' ?>><?= Html::encode($spName) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <select id="jitsi-member-filter" class="jitsi-dropdown-select">
                <option value=""><?= Yii::t('JitsiMeetCloud8x8Module.base', 'All Members') ?></option>
                <?php if (!Yii::$app->user->isGuest): ?>
                    <option value="<?= Yii::$app->user->id ?>" <?= $filterCreatorId == Yii::$app->user->id ? 'selected' : '' ?>><?= Yii::t('JitsiMeetCloud8x8Module.base', 'My Streams') ?></option>
                <?php endif; ?>
            </select>
            <?php if (!empty($filterSpaceId) || !empty($filterCreatorId)): ?>
                <a href="<?= Url::to(['index', 'filter' => $filter]) ?>" class="jitsi-clear-filters-btn">
                    <i class="fa fa-times"></i>
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
                    params.delete('page');
                    
                    window.location.href = window.location.pathname + '?' + params.toString();
                }
                
                var spaceSelect = document.getElementById('jitsi-space-filter');
                var memberSelect = document.getElementById('jitsi-member-filter');
                if (spaceSelect) spaceSelect.addEventListener('change', applyFilters);
                if (memberSelect) memberSelect.addEventListener('change', applyFilters);
            })();
        </script>
        <?php endif; ?>

        <div id="jitsi-join-panel" class="jitsi-actions">
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

    <?php
    $activeGridStreams = [];
    $endedGridStreams = [];

    if ($filter === 'all') {
        $activeGridStreams = array_merge($activeStreams, $scheduledStreams);
        $endedGridStreams = $endedStreams;
    } elseif ($filter === 'live') {
        $activeGridStreams = $activeStreams;
    } elseif ($filter === 'scheduled') {
        $activeGridStreams = $scheduledStreams;
    } elseif ($filter === 'ended') {
        $endedGridStreams = $endedStreams;
    }

    $showActiveGrid = ($filter !== 'ended');
    $showEndedGrid = ($filter === 'all' || $filter === 'ended');
    ?>

    <?php if ($showActiveGrid): ?>
    <div id="jitsi-active-grid" class="live-stream-grid">
        <?php if (empty($activeGridStreams)): ?>
            <div class="col-md-12 text-center text-muted" style="grid-column: 1 / -1; padding: 40px;">
                <i class="fa fa-film fa-3x jitsi-empty-icon" aria-hidden="true"></i><br>
                <?= Yii::t('JitsiMeetCloud8x8Module.base', 'No streams found.') ?>
            </div>
        <?php else: ?>
            <?php foreach ($activeGridStreams as $stream): ?>
                <?= $this->render('_stream_card', ['stream' => $stream]) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($showEndedGrid): ?>
    <div id="jitsi-ended-grid" class="live-stream-grid">
        <?php if (empty($endedGridStreams)): ?>
            <div class="col-md-12 text-center text-muted" style="grid-column: 1 / -1; padding: 40px;">
                <i class="fa fa-history fa-3x jitsi-empty-icon" aria-hidden="true"></i><br>
                <?= Yii::t('JitsiMeetCloud8x8Module.base', 'No streams found.') ?>
            </div>
        <?php else: ?>
            <?php foreach ($endedGridStreams as $stream): ?>
                <?= $this->render('_stream_card', ['stream' => $stream]) ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
        
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

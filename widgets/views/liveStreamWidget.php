<?php

use humhub\widgets\PanelMenu;
use yii\helpers\Html;

/* @var $activeStreams \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream[] */
/* @var $title string */
?>
<div class="panel panel-default" id="jitsi-live-stream-widget">
    <div class="panel-heading">
        <?= Html::encode($title) ?>
        <?= PanelMenu::widget(['id' => 'jitsi-live-stream-widget']); ?>
    </div>
    <div class="panel-body">
        <?php foreach ($activeStreams as $stream): ?>
            <?= $this->render('../../views/room/_stream_card', ['stream' => $stream]) ?>
        <?php endforeach; ?>
    </div>
</div>

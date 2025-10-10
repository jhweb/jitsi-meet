<?php

/** @noinspection MissedFieldInspection */

use humhub\widgets\TopMenu;
use humhub\modules\content\widgets\WallCreateContentForm;

return [
    'id' => 'jitsi-meet-cloud-8x8',
    'class' => 'humhubContrib\modules\jitsiMeetCloud8x8\Module',
    'namespace' => 'humhubContrib\modules\jitsiMeetCloud8x8',
    'events' => [
        ['class' => TopMenu::class, 'event' => TopMenu::EVENT_INIT, 'callback' => ['humhubContrib\modules\jitsiMeetCloud8x8\Events', 'onTopMenuInit']],
        ['class' => WallCreateContentForm::class, 'event' => 'init', 'callback' => ['humhubContrib\modules\jitsiMeetCloud8x8\Events', 'onWallCreateContentFormInit']],
    ],
    'urlManagerRules' => [
        '/conference/<name>' => 'jitsi-meet-cloud-8x8/room/open'
    ]
];
?>
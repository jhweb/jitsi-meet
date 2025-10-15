<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8;

use humhub\modules\ui\menu\MenuLink;
use humhub\widgets\TopMenu;
use humhub\modules\content\widgets\WallCreateContentMenu;
use humhub\modules\user\widgets\AccountMenu;
use humhubContrib\modules\jitsiMeetCloud8x8\permissions\CanAccess;
use humhubContrib\modules\jitsiMeetCloud8x8\permissions\CreateVideoChat;
use Yii;

class Events
{

    public static function onTopMenuInit($event)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->can(CanAccess::class)) {
            return;
        }

        /** @var TopMenu $topNav */
        $topNav = $event->sender;

        /** @var Module $module */
        $module = Yii::$app->getModule('jitsi-meet-cloud-8x8');

        $topNav->addEntry(new MenuLink([
            'label' => Yii::t('JitsiMeetCloud8x8Module.base', $module->getSettingsForm()->menuTitle),
            'url' => ['/jitsi-meet-cloud-8x8/room'],
            'icon' => 'video-camera',
            'isActive' => MenuLink::isActiveState('jitsi-meet-cloud-8x8', 'room'),
            'sortOrder' => 400,
        ]));
    }


    /**
     * Hook into user account menu to add video chat preferences
     */
    public static function onAccountMenuInit($event)
    {
        if (Yii::$app->user->isGuest) {
            return;
        }

        /** @var AccountMenu $menu */
        $menu = $event->sender;

        $menu->addEntry(new MenuLink([
            'label' => Yii::t('JitsiMeetCloud8x8Module.base', 'Video Chat Notifications'),
            'url' => ['/jitsi-meet-cloud-8x8/preferences'],
            'icon' => 'bell',
            'sortOrder' => 500,
        ]));
    }

}

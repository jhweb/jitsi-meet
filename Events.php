<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8;

use humhub\modules\ui\menu\MenuLink;
use humhub\widgets\TopMenu;
use humhub\modules\content\widgets\WallCreateContentMenu;
use humhub\modules\user\widgets\AccountMenu;
use humhubContrib\modules\jitsiMeetCloud8x8\permissions\CanAccess;
use humhubContrib\modules\jitsiMeetCloud8x8\permissions\CreateVideoChat;
use humhubContrib\modules\jitsiMeetCloud8x8\widgets\QuickVideoChatButton;
use humhub\models\ModuleEnabled;
use Yii;

class Events
{

    public static function onTopMenuInit($event)
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->can(CanAccess::class)) {
            return;
        }

        // Check if our module is actually enabled before showing menu
        if (!ModuleEnabled::findOne(['module_id' => 'jitsi-meet-cloud-8x8'])) {
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

    /**
     * Hook into space wall composer menu to add Quick Video Chat button
     * This is now handled automatically by WallCreateContentMenu based on content classes
     * 
     * @param $event
     */
    public static function onWallCreateContentMenuInit($event)
    {
        // This method is no longer needed as WallCreateContentMenu automatically
        // generates menu entries from content classes registered in Module::getContentClasses()
        // The InstantVideoChat content class and WallStreamEntryInstantVideoChat widget
        // will automatically create the "Video Chat" tab in the content creation menu
    }

}

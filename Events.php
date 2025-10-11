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
     * Hook into space stream composer menu to add video chat button
     */
    public static function onWallCreateContentMenuInit($event)
    {
        /** @var WallCreateContentMenu $menu */
        $menu = $event->sender;
        
        // Only add to space contexts
        if ($menu->contentContainer instanceof \humhub\modules\space\models\Space) {
            // Check if user has permission to create video chats
            if ($menu->contentContainer->can(CreateVideoChat::class)) {
                $menu->addEntry(new MenuLink([
                    'label' => Yii::t('JitsiMeetCloud8x8Module.base', 'Start Video Chat'),
                    'icon' => 'video-camera',
                    'url' => '#',
                    'sortOrder' => 100,
                    'htmlOptions' => [
                        'data-action-click' => 'ui.modal.load',
                        'data-action-url' => $menu->contentContainer->createUrl('/jitsi-meet-cloud-8x8/space/quick-post-modal'),
                    ],
                ]));
            }
        }
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

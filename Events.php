<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8;

use humhub\modules\ui\menu\MenuLink;
use humhub\widgets\TopMenu;
use humhubContrib\modules\jitsiMeetCloud8x8\permissions\CanAccess;
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

        // Check for active streams to display notification dot
        $activeCount = \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream::find()
            ->where(['status' => \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream::STATUS_LIVE])
            ->count();
        
        $entryOptions = [
            'label' => Yii::t('JitsiMeetCloud8x8Module.base', $module->getSettingsForm()->menuTitle),
            'url' => ['/jitsi-meet-cloud-8x8/room'],
            'icon' => 'video-camera',
            'isActive' => MenuLink::isActiveState('jitsi-meet-cloud-8x8', 'room'),
            'sortOrder' => 400,
        ];
        
        if ($activeCount > 0) {
            $entryOptions['htmlOptions'] = ['class' => 'jitsi-menu-live'];
            
            // Register the indicator CSS globally so it shows on all pages
            Yii::$app->view->registerCss('
                @keyframes blinkRed {
                    0% { opacity: 1; }
                    50% { opacity: 0.4; }
                    100% { opacity: 1; }
                }
                .jitsi-menu-live .fa-video-camera {
                    position: relative;
                }
                .jitsi-menu-live .fa-video-camera::after {
                    content: "";
                    position: absolute;
                    top: -2px;
                    right: -2px;
                    width: 6px;
                    height: 6px;
                    background-color: #ff0000;
                    border-radius: 50%;
                    animation: blinkRed 1.5s infinite;
                    border: 1px solid #fff;
                }
            ', [], 'jitsi-menu-live-indicator');
        }

        $topNav->addEntry(new MenuLink($entryOptions));
    }

}

<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\widgets;

use humhub\components\Widget;
use humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream;
use humhubContrib\modules\jitsiMeetCloud8x8\permissions\CanAccess;
use Yii;

class LiveStreamWidget extends Widget
{
    public function run()
    {
        // Check if user has access to the module
        if (Yii::$app->user->isGuest || !Yii::$app->user->can(CanAccess::class)) {
            return '';
        }

        $module = Yii::$app->getModule('jitsi-meet-cloud-8x8');
        $settings = $module->settings;

        // Check if widget is enabled in settings
        if (!$settings->get('enableLiveStreamWidget')) {
            return '';
        }

        // Check for active streams
        $activeStreams = JitsiLiveStream::find()
            ->where(['status' => JitsiLiveStream::STATUS_LIVE])
            ->all();

        if (empty($activeStreams)) {
            return '';
        }

        return $this->render('liveStreamWidget', [
            'activeStreams' => $activeStreams,
            'title' => $settings->get('liveStreamWidgetTitle', 'Live Streams')
        ]);
    }
}

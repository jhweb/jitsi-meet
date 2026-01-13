<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\widgets;

use humhub\components\Widget;
use Yii;

class StreamGuide extends Widget
{
    public function run()
    {
        $settings = Yii::$app->getModule('jitsi-meet-cloud-8x8')->settings;
        
        if (!$settings->get('enableTour', 1)) {
            return '';
        }

        $forceStart = false;
        if (Yii::$app->request instanceof \yii\web\Request) {
            $forceStart = Yii::$app->request->get('tour') ? true : false;
        }
        
        return $this->render('streamGuide', ['forceStart' => $forceStart]);
    }
}

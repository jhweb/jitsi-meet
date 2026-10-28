<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\assets;

use humhubContrib\modules\jitsiMeetCloud8x8\Module;
use Yii;
use yii\web\AssetBundle;
use yii\web\View;

class Assets extends AssetBundle
{

    public $publishOptions = [];

    public $jsOptions = [
        'position' => View::POS_BEGIN
    ];

    public $css = [
        'jitsi-meet.css'
    ];

    public function init()
    {
        $this->initJitsiApiJs();
        $this->sourcePath = dirname(__FILE__) . '/../resources';
        if (YII_DEBUG) {
            $this->publishOptions['forceCopy'] = true;
        }
        parent::init();
    }

    private function initJitsiApiJs()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('jitsi-meet-cloud-8x8');
        if ($module instanceof Module) {
            $this->js = [
                'humhub.jitsiMeet.js'
            ];
        }
    }

}

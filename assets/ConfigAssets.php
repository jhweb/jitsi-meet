<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\assets;

use yii\web\AssetBundle;

class ConfigAssets extends AssetBundle
{
    public $publishOptions = [
        'forceCopy' => true
    ];

    public $js = [
        'humhub.jitsiMeet.config.js'
    ];

    public function init()
    {
        $this->sourcePath = dirname(__FILE__) . '/../resources';
        parent::init();
    }
}

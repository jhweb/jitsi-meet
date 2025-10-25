<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\assets;

use humhubContrib\modules\jitsiMeetCloud8x8\Module;
use Yii;
use yii\web\AssetBundle;
use yii\web\View;

class Assets extends AssetBundle
{

    public $publishOptions = [
        'forceCopy' => true
    ];

    public $jsOptions = [
        'position' => View::POS_BEGIN
    ];

    public function init()
    {
        $this->sourcePath = dirname(__FILE__) . '/../resources';
        $this->js = [
            'https://meet.jit.si/external_api.js',
            'humhub.jitsiMeet.js'
        ];
        parent::init();
    }

}
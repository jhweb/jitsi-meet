<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\assets;

use humhub\assets\AppAsset;
use humhub\assets\ClipboardJsAsset;
use humhubContrib\modules\jitsiMeetCloud8x8\Module;
use humhub\modules\ui\view\components\View;
use Yii;
use yii\web\AssetBundle;
use yii\web\View as WebView;

class Assets extends AssetBundle
{

    public $publishOptions = [
        'forceCopy' => true
    ];

    public $jsOptions = [
        'position' => WebView::POS_BEGIN
    ];

    public $css = [
        'jitsi-meet.css'
    ];

    public function init()
    {
        $this->initJitsiApiJs();
        $this->sourcePath = dirname(__FILE__) . '/../resources';
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

class ConfigAssets extends AssetBundle
{
    public $sourcePath;

    public function init()
    {
        $this->sourcePath = dirname(__FILE__) . '/../resources';
        parent::init();
    }

    public $js = [
        'humhub.jitsiMeet.config.js',
    ];

    public $depends = [
        AppAsset::class,
        ClipboardJsAsset::class,
    ];

    /**
     * @param View $view
     * @return AssetBundle
     */
    public static function register($view)
    {
        $view->registerJsConfig('jitsiMeet.config', [
            'text' => [
                'copied' => Yii::t('JitsiMeetCloud8x8Module.base', 'Webhook URL copied to clipboard.'),
                'copyError' => Yii::t('JitsiMeetCloud8x8Module.base', 'Could not copy webhook URL to clipboard.'),
            ],
        ]);

        return parent::register($view);
    }
}

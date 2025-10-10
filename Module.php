<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8;

use humhubContrib\modules\jitsiMeetCloud8x8\models\SettingsForm;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\components\ContentContainerModule;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use yii\helpers\Url;
use Yii;

class Module extends ContentContainerModule
{
    public $resourcesPath = 'resources';

    private $_settingsForm = null;

    /**
     * @return SettingsForm
     */
    public function getSettingsForm()
    {
        if ($this->_settingsForm === null) {
            $this->_settingsForm = new SettingsForm();
        }

        return $this->_settingsForm;
    }


    /**
     * @inheritdoc
     */
    public function getConfigUrl()
    {
        return Url::to(['/jitsi-meet-cloud-8x8/config']);
    }

    /**
     * @inheritdoc
     */
    public function getPermissions($contentContainer = null)
    {
        return [
            new \humhubContrib\modules\jitsiMeetCloud8x8\permissions\CanAccess(),
            new \humhubContrib\modules\jitsiMeetCloud8x8\permissions\CreateVideoChat(),
            new \humhubContrib\modules\jitsiMeetCloud8x8\permissions\JoinVideoChat(),
            new \humhubContrib\modules\jitsiMeetCloud8x8\permissions\EnableRecording(),
            new \humhubContrib\modules\jitsiMeetCloud8x8\permissions\EnableLivestreaming(),
            new \humhubContrib\modules\jitsiMeetCloud8x8\permissions\ManageRecordings(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getContentClasses(?ContentContainerActiveRecord $contentContainer = null): array
    {
        return [
            \humhubContrib\modules\jitsiMeetCloud8x8\models\InstantVideoChat::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getContentContainerTypes()
    {
        return [Space::class, User::class];
    }
    
    /**
     * @inheritdoc
     */
    public function disable()
    {
        // Clean up space settings, participant tracking
        parent::disable();
    }
    
    /**
     * Check if Calendar module is available
     * @return bool
     */
    public function isCalendarModuleAvailable(): bool
    {
        return Yii::$app->hasModule('calendar');
    }

}

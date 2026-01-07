<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\permissions;

use humhub\libs\BasePermission;
use Yii;

/**
 * Permission to schedule livestream events in advance
 */
class CanSchedule extends BasePermission
{
    /**
     * @inheritdoc
     */
    protected $id = 'can_schedule_livestream';

    /**
     * @inheritdoc
     */
    protected $moduleId = 'jitsi-meet-cloud-8x8';

    /**
     * @inheritdoc
     */
    public $defaultAllowedGroups = [
        \humhub\modules\space\models\Space::USERGROUP_OWNER,
        \humhub\modules\space\models\Space::USERGROUP_ADMIN,
        \humhub\modules\space\models\Space::USERGROUP_MODERATOR,
    ];

    /**
     * @inheritdoc
     */
    protected $fixedGroups = [
        \humhub\modules\space\models\Space::USERGROUP_GUEST,
    ];

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Yii::t('JitsiMeetCloud8x8Module.permissions', 'Schedule Livestreams');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Yii::t('JitsiMeetCloud8x8Module.permissions', 'Allows scheduling livestream events in advance with calendar integration.');
    }
}

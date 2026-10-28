<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\models;

use Yii;

class JoinRoomForm extends \yii\base\Model
{

    public $room;
    public $newWindow;

   /**
     * @var boolean
     */
    public $lobbyEnabled = false;

    /**
     * @var string|null Selected content container GUID (space or profile)
     */
    public $targetContainer;

    /**
     * @var string|null Optional description for the stream
     */
    public $description;

    /**
     * @var boolean Whether the stream is public (visible to non-members)
     */
    public $isPublic = true;

    public function rules()
    {
        return [
            [['room', 'targetContainer', 'description'], 'string'],
            [['newWindow', 'lobbyEnabled', 'isPublic'], 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'room' => Yii::t('JitsiMeetCloud8x8Module.base', 'Name'),
            'newWindow' => Yii::t('JitsiMeetCloud8x8Module.base', 'Open in new window?'),
            'lobbyEnabled' => Yii::t('JitsiMeetCloud8x8Module.base', 'Enable Waiting Room (Lobby)'),
            'description' => Yii::t('JitsiMeetCloud8x8Module.base', 'Description'),
            'isPublic' => Yii::t('JitsiMeetCloud8x8Module.base', 'Public'),
        ];
    }
}
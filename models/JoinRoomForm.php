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

    public function rules()
    {
        return [
            [['room'], 'string'],
            [['newWindow', 'lobbyEnabled'], 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'room' => Yii::t('JitsiMeetCloud8x8Module.base', 'Name'),
            'newWindow' => Yii::t('JitsiMeetCloud8x8Module.base', 'Open in new window?'),
            'lobbyEnabled' => Yii::t('JitsiMeetCloud8x8Module.base', 'Enable Waiting Room (Lobby)'),
        ];
    }
}
<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;
use Yii;

/**
 * This is the model class for table "jitsi_live_stream".
 *
 * @property int $id
 * @property string $room_name
 * @property string $session_id
 * @property int $status 0=ended, 1=live
 * @property string $start_time
 * @property string $end_time
 * @property int $creator_id
 * @property int $participant_count
 * @property string $stream_url
 * @property string $recording_url
 * @property string $event_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $creator
 */
class JitsiLiveStream extends ActiveRecord
{
    const STATUS_ENDED = 0;
    const STATUS_LIVE = 1;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'jitsi_live_stream';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['room_name'], 'required'],
            [['status', 'creator_id', 'participant_count', 'active_count'], 'integer'],
            [['start_time', 'end_time', 'created_at', 'updated_at'], 'safe'],
            [['room_name', 'session_id', 'stream_url', 'event_id'], 'string', 'max' => 255],
            [['recording_url', 'transcription_url', 'chat_log_url', 'file_urls', 'reactions'], 'safe'],
            [['event_id'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'room_name' => 'Room Name',
            'session_id' => 'Session ID',
            'status' => 'Status',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'creator_id' => 'Creator',
            'participant_count' => 'Participants',
            'stream_url' => 'Stream URL',
            'recording_url' => 'Recording URL',
            'transcription_url' => 'Transcript',
            'chat_log_url' => 'Chat Log',
            'file_urls' => 'Files',
            'reactions' => 'Reactions',
            'event_id' => 'Event ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            $this->created_at = new \yii\db\Expression('NOW()');
        }
        $this->updated_at = new \yii\db\Expression('NOW()');

        return parent::beforeSave($insert);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(User::class, ['id' => 'creator_id']);
    }

    public function getDuration()
    {
        if (empty($this->start_time)) {
            return '';
        }

        $end = $this->end_time ? strtotime($this->end_time) : time();
        $start = strtotime($this->start_time);
        $diff = $end - $start;

        if ($diff < 60) {
            return $diff . 's';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . 'm';
        }
        return floor($diff / 3600) . 'h ' . floor(($diff % 3600) / 60) . 'm';
    }

    /**
     * Decode file URLs from JSON
     * @return array
     */
    public function getFiles()
    {
        if (empty($this->file_urls)) {
            return [];
        }
        $files = json_decode($this->file_urls, true);
        return is_array($files) ? $files : [];
    }

    /**
     * Add a file URL to the list
     * @param string $url
     * @return bool
     */
    public function addFileUrl($url)
    {
        $files = $this->getFiles();
        if (!in_array($url, $files)) {
            $files[] = $url;
            $this->file_urls = json_encode($files);
            return true;
        }
        return false;
    }

    /**
     * Get parsed reactions
     * @return array
     */
    public function getReactions()
    {
        if (empty($this->reactions)) {
            return [];
        }
        $reactions = json_decode($this->reactions, true);
        return is_array($reactions) ? $reactions : [];
    }
}

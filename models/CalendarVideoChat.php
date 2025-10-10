<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\models;

use humhub\modules\user\models\User;
use Yii;

/**
 * This is the model class for table "jitsi_calendar_video_chat".
 *
 * @property int $id
 * @property int $calendar_entry_id Calendar entry ID
 * @property string $room_name Generated room name
 * @property string|null $custom_notification_message Override default notification text
 * @property bool $enable_recording Enable recording
 * @property bool $enable_streaming Enable streaming
 * @property string|null $youtube_stream_key YouTube stream key
 * @property int $created_by User who created the video chat
 * @property string $created_at Record creation time
 * @property string $updated_at Record update time
 *
 * @property \humhub\modules\calendar\models\CalendarEntry $calendarEntry
 * @property User $createdBy
 * @property VideoChatParticipant[] $participants
 */
class CalendarVideoChat extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'jitsi_calendar_video_chat';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['calendar_entry_id', 'room_name', 'created_by'], 'required'],
            [['calendar_entry_id', 'created_by'], 'integer'],
            [['custom_notification_message'], 'string'],
            [['enable_recording', 'enable_streaming'], 'boolean'],
            [['created_at', 'updated_at'], 'safe'],
            [['room_name', 'youtube_stream_key'], 'string', 'max' => 255],
            [['calendar_entry_id'], 'unique'],
            [['calendar_entry_id'], 'exist', 'skipOnError' => true, 'targetClass' => 'humhub\modules\calendar\models\CalendarEntry', 'targetAttribute' => ['calendar_entry_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'calendar_entry_id' => Yii::t('JitsiMeetCloud8x8Module.base', 'Calendar Entry'),
            'room_name' => Yii::t('JitsiMeetCloud8x8Module.base', 'Room Name'),
            'custom_notification_message' => Yii::t('JitsiMeetCloud8x8Module.base', 'Custom Notification Message'),
            'enable_recording' => Yii::t('JitsiMeetCloud8x8Module.base', 'Enable Recording'),
            'enable_streaming' => Yii::t('JitsiMeetCloud8x8Module.base', 'Enable Streaming'),
            'youtube_stream_key' => Yii::t('JitsiMeetCloud8x8Module.base', 'YouTube Stream Key'),
            'created_by' => Yii::t('JitsiMeetCloud8x8Module.base', 'Created By'),
            'created_at' => Yii::t('JitsiMeetCloud8x8Module.base', 'Created At'),
            'updated_at' => Yii::t('JitsiMeetCloud8x8Module.base', 'Updated At'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_at = date('Y-m-d H:i:s');
                
                // Generate room name if not provided
                if (empty($this->room_name)) {
                    $this->room_name = $this->generateRoomName();
                }
            }
            $this->updated_at = date('Y-m-d H:i:s');
            return true;
        }
        return false;
    }

    /**
     * Generate room name based on calendar entry
     * @return string
     */
    private function generateRoomName()
    {
        $calendarEntry = $this->calendarEntry;
        if (!$calendarEntry) {
            return 'VideoChat-' . date('Ymd-His');
        }
        
        // Use calendar entry title as base
        $title = $calendarEntry->title;
        $title = preg_replace('/[^A-Za-z0-9]/', '', $title);
        $title = substr($title, 0, 20); // Limit length
        
        if (empty($title)) {
            $title = 'VideoChat';
        }
        
        $timestamp = date('Ymd-His');
        return $title . '-' . $timestamp;
    }

    /**
     * Gets query for [[CalendarEntry]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCalendarEntry()
    {
        return $this->hasOne('humhub\modules\calendar\models\CalendarEntry', ['id' => 'calendar_entry_id']);
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[Participants]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParticipants()
    {
        return $this->hasMany(VideoChatParticipant::class, ['calendar_entry_id' => 'calendar_entry_id']);
    }

    /**
     * Get the join URL for this video chat
     * @return string
     */
    public function getJoinUrl()
    {
        return \yii\helpers\Url::to(['/jitsi-meet-cloud-8x8/room/open', 'name' => $this->room_name]);
    }

    /**
     * Get the notification message for this video chat
     * @return string
     */
    public function getNotificationMessage()
    {
        if (!empty($this->custom_notification_message)) {
            return $this->custom_notification_message;
        }
        
        $calendarEntry = $this->calendarEntry;
        if ($calendarEntry) {
            return $calendarEntry->description ?: $calendarEntry->title;
        }
        
        return Yii::t('JitsiMeetCloud8x8Module.base', 'Join our video chat!');
    }

    /**
     * Check if user can enable recording for this video chat
     * @param User|null $user
     * @return bool
     */
    public function canEnableRecording($user = null)
    {
        if (!$user) {
            $user = Yii::$app->user->getIdentity();
        }
        
        if (!$user) {
            return false;
        }
        
        // Creator can always enable recording
        if ($this->created_by === $user->id) {
            return true;
        }
        
        // Check space permissions
        $calendarEntry = $this->calendarEntry;
        if ($calendarEntry && $calendarEntry->content && $calendarEntry->content->container) {
            return $calendarEntry->content->container->can(\humhubContrib\modules\jitsiMeetCloud8x8\permissions\EnableRecording::class);
        }
        
        return false;
    }

    /**
     * Check if user can enable streaming for this video chat
     * @param User|null $user
     * @return bool
     */
    public function canEnableStreaming($user = null)
    {
        if (!$user) {
            $user = Yii::$app->user->getIdentity();
        }
        
        if (!$user) {
            return false;
        }
        
        // Creator can always enable streaming
        if ($this->created_by === $user->id) {
            return true;
        }
        
        // Check space permissions
        $calendarEntry = $this->calendarEntry;
        if ($calendarEntry && $calendarEntry->content && $calendarEntry->content->container) {
            return $calendarEntry->content->container->can(\humhubContrib\modules\jitsiMeetCloud8x8\permissions\EnableLivestreaming::class);
        }
        
        return false;
    }

    /**
     * Get the number of participants who clicked "Notify Me"
     * @return int
     */
    public function getNotifyMeCount()
    {
        return VideoChatParticipant::find()
            ->where(['calendar_entry_id' => $this->calendar_entry_id, 'notify_me' => true])
            ->count();
    }

    /**
     * Check if a user has clicked "Notify Me" for this video chat
     * @param User|null $user
     * @return bool
     */
    public function hasUserNotifyMe($user = null)
    {
        if (!$user) {
            $user = Yii::$app->user->getIdentity();
        }
        
        if (!$user) {
            return false;
        }
        
        $participant = VideoChatParticipant::find()
            ->where(['calendar_entry_id' => $this->calendar_entry_id, 'user_id' => $user->id])
            ->one();
            
        return $participant ? $participant->notify_me : false;
    }

    /**
     * Toggle "Notify Me" for a user
     * @param User|null $user
     * @return bool
     */
    public function toggleNotifyMe($user = null)
    {
        if (!$user) {
            $user = Yii::$app->user->getIdentity();
        }
        
        if (!$user) {
            return false;
        }
        
        $participant = VideoChatParticipant::find()
            ->where(['calendar_entry_id' => $this->calendar_entry_id, 'user_id' => $user->id])
            ->one();
            
        if (!$participant) {
            $participant = new VideoChatParticipant();
            $participant->calendar_entry_id = $this->calendar_entry_id;
            $participant->user_id = $user->id;
            $participant->notify_me = true;
            $participant->created_at = date('Y-m-d H:i:s');
            return $participant->save();
        } else {
            $participant->notify_me = !$participant->notify_me;
            return $participant->save();
        }
    }
}

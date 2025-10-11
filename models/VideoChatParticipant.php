<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\models;

use humhub\modules\user\models\User;
use Yii;

/**
 * This is the model class for table "jitsi_video_chat_participant".
 *
 * @property int $id
 * @property int $calendar_entry_id Calendar entry ID
 * @property int $user_id User ID
 * @property bool $notify_me User wants notifications
 * @property bool $notification_sent_24h 24h reminder sent
 * @property bool $notification_sent_5min 5min reminder sent
 * @property string $created_at Record creation time
 *
 * @property \humhub\modules\calendar\models\CalendarEntry $calendarEntry
 * @property User $user
 */
class VideoChatParticipant extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'jitsi_video_chat_participant';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['calendar_entry_id', 'user_id'], 'required'],
            [['calendar_entry_id', 'user_id'], 'integer'],
            [['notify_me', 'notification_sent_24h', 'notification_sent_5min'], 'boolean'],
            [['created_at'], 'safe'],
            [['calendar_entry_id', 'user_id'], 'unique', 'targetAttribute' => ['calendar_entry_id', 'user_id']],
            [['calendar_entry_id'], 'exist', 'skipOnError' => true, 'targetClass' => 'humhub\modules\calendar\models\CalendarEntry', 'targetAttribute' => ['calendar_entry_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
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
            'user_id' => Yii::t('JitsiMeetCloud8x8Module.base', 'User'),
            'notify_me' => Yii::t('JitsiMeetCloud8x8Module.base', 'Notify Me'),
            'notification_sent_24h' => Yii::t('JitsiMeetCloud8x8Module.base', '24h Notification Sent'),
            'notification_sent_5min' => Yii::t('JitsiMeetCloud8x8Module.base', '5min Notification Sent'),
            'created_at' => Yii::t('JitsiMeetCloud8x8Module.base', 'Created At'),
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
            }
            return true;
        }
        return false;
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
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Toggle "Notify Me" for this participant
     * @return bool
     */
    public function toggleNotifyMe()
    {
        $this->notify_me = !$this->notify_me;
        return $this->save();
    }

    /**
     * Check if a reminder should be sent for the given interval
     * @param int $intervalMinutes
     * @return bool
     */
    public function shouldSendReminder($intervalMinutes)
    {
        if (!$this->notify_me) {
            return false;
        }
        
        $calendarEntry = $this->calendarEntry;
        if (!$calendarEntry) {
            return false;
        }
        
        $startTime = strtotime($calendarEntry->start_datetime);
        $now = time();
        $timeUntilStart = $startTime - $now;
        $intervalSeconds = $intervalMinutes * 60;
        
        // Check if we're within the reminder window
        $reminderWindow = 300; // 5 minutes window
        if ($timeUntilStart > $intervalSeconds + $reminderWindow || $timeUntilStart < $intervalSeconds - $reminderWindow) {
            return false;
        }
        
        // Check if reminder was already sent
        if ($intervalMinutes >= 1440) { // 24 hours
            return !$this->notification_sent_24h;
        } elseif ($intervalMinutes <= 5) { // 5 minutes
            return !$this->notification_sent_5min;
        }
        
        return true;
    }

    /**
     * Mark a reminder as sent for the given interval
     * @param int $intervalMinutes
     * @return bool
     */
    public function markReminderSent($intervalMinutes)
    {
        if ($intervalMinutes >= 1440) { // 24 hours
            $this->notification_sent_24h = true;
        } elseif ($intervalMinutes <= 5) { // 5 minutes
            $this->notification_sent_5min = true;
        }
        
        return $this->save();
    }

    /**
     * Send notification to this participant
     * @param string $type
     * @param array $params
     * @return bool
     */
    public function notifyUser($type, $params = [])
    {
        if (!$this->user) {
            return false;
        }
        
        $notificationClass = "\\humhubContrib\\modules\\jitsiMeetCloud8x8\\notifications\\{$type}";
        
        if (!class_exists($notificationClass)) {
            return false;
        }
        
        $notification = new $notificationClass();
        
        // Set notification properties
        foreach ($params as $key => $value) {
            if (property_exists($notification, $key)) {
                $notification->$key = $value;
            }
        }
        
        return $notification->send($this->user);
    }

    /**
     * Get all participants who should receive a reminder
     * @param int $intervalMinutes
     * @return VideoChatParticipant[]
     */
    public static function getParticipantsForReminder($intervalMinutes)
    {
        $query = self::find()
            ->with(['user', 'calendarEntry'])
            ->where(['notify_me' => true]);
        
        // Add time-based conditions
        $now = time();
        $intervalSeconds = $intervalMinutes * 60;
        $reminderWindow = 300; // 5 minutes window
        
        $startTimeMin = $now + $intervalSeconds - $reminderWindow;
        $startTimeMax = $now + $intervalSeconds + $reminderWindow;
        
        $query->andWhere([
            'between',
            'calendar_entry.start_datetime',
            date('Y-m-d H:i:s', $startTimeMin),
            date('Y-m-d H:i:s', $startTimeMax)
        ]);
        
        // Exclude already sent reminders
        if ($intervalMinutes >= 1440) { // 24 hours
            $query->andWhere(['notification_sent_24h' => false]);
        } elseif ($intervalMinutes <= 5) { // 5 minutes
            $query->andWhere(['notification_sent_5min' => false]);
        }
        
        return $query->all();
    }
}


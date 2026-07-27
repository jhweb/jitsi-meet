<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\models;

use DateTime;
use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;
use Yii;
use yii\helpers\Url;

/**
 * This is the model class for table "jitsi_live_stream".
 *
 * @property int $id
 * @property string $room_name
 * @property string $title
 * @property string $session_id
 * @property int $status 0=ended, 1=live, 2=scheduled
 * @property string $start_time
 * @property string $end_time
 * @property string $scheduled_start
 * @property string $scheduled_end
 * @property int $all_day
 * @property string $rrule
 * @property string $exdate
 * @property int $parent_event_id
 * @property string $recurrence_id
 * @property string $uid
 * @property string $description
 * @property string $timezone
 * @property int $creator_id
 * @property int $participant_count
 * @property string $stream_url
 * @property string $recording_url
 * @property string $event_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $creator
 * @property JitsiLiveStream $parentEvent
 */
class JitsiLiveStream extends ActiveRecord
{
    const STATUS_ENDED = 0;
    const STATUS_LIVE = 1;
    const STATUS_SCHEDULED = 2;

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
            [['status', 'creator_id', 'space_id', 'participant_count', 'active_count', 'has_recording', 'all_day', 'parent_event_id', 'calendar_entry_id', 'lobby_enabled'], 'integer'],
            [['start_time', 'end_time', 'created_at', 'updated_at', 'scheduled_start', 'scheduled_end'], 'safe'],
            [['room_name', 'session_id', 'stream_url', 'event_id', 'title', 'rrule', 'uid', 'timezone'], 'string', 'max' => 255],
            [['recurrence_id'], 'string', 'max' => 50],
            [['description', 'exdate'], 'string'],
            // URL/metadata fields are webhook-written only — never mass-assignable from forms.
            [['reactions', 'speaker_stats', 'polls', 'feedback'], 'safe'],
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
            'title' => 'Title',
            'session_id' => 'Session ID',
            'status' => 'Status',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'scheduled_start' => 'Scheduled Start',
            'scheduled_end' => 'Scheduled End',
            'all_day' => 'All Day Event',
            'rrule' => 'Recurrence Rule',
            'exdate' => 'Exception Dates',
            'parent_event_id' => 'Parent Event',
            'recurrence_id' => 'Recurrence ID',
            'uid' => 'Unique ID',
            'description' => 'Description',
            'timezone' => 'Timezone',
            'creator_id' => 'Creator',
            'participant_count' => 'Participants',
            'stream_url' => 'Stream URL',
            'recording_url' => 'Recording URL',
            'transcription_url' => 'Transcript',
            'chat_log_url' => 'Chat Log',
            'file_urls' => 'Files',
            'reactions' => 'Reactions',
            'highlights_url' => 'Highlights',
            'screen_sharing_url' => 'Screen Sharing History',
            'speaker_stats' => 'Speaker Stats',
            'rtcstats_url' => 'RTC Stats URL',
            'ytstream_url' => 'YouTube Stream URL',
            'has_recording' => 'Has Recording',
            'event_id' => 'Event ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            $this->created_at = new \yii\db\Expression('NOW()');
            // Generate UID for calendar integration if not set
            if (empty($this->uid)) {
                $host = 'cli';
                if (Yii::$app->request instanceof \yii\web\Request) {
                    $host = Yii::$app->request->hostName;
                } else {
                    $host = Yii::$app->params['settings']['baseUrl'] ?? 'console';
                    $host = preg_replace('#^https?://#', '', $host);
                }
                $this->uid = uniqid('jitsi-') . '@' . $host;
            }
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentEvent()
    {
        return $this->hasOne(self::class, ['id' => 'parent_event_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCalendarEntry()
    {
        return $this->hasOne(\humhub\modules\calendar\models\CalendarEntry::class, ['id' => 'calendar_entry_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSpace()
    {
        return $this->hasOne(\humhub\modules\space\models\Space::class, ['id' => 'space_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRecurrenceInstances()
    {
        return $this->hasMany(self::class, ['parent_event_id' => 'id']);
    }

    /**
     * Check if this is a scheduled event
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->status == self::STATUS_SCHEDULED;
    }

    /**
     * Check if this is a recurring event
     * @return bool
     */
    public function isRecurrent(): bool
    {
        return !empty($this->rrule);
    }

    /**
     * Check if this is a recurrence instance (not the root)
     * @return bool
     */
    public function isRecurrenceInstance(): bool
    {
        return !empty($this->parent_event_id);
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
     * Get time until scheduled start
     * @return string Human-readable countdown
     */
    public function getCountdown(): string
    {
        if (empty($this->scheduled_start)) {
            return '';
        }

        $now = time();
        $start = strtotime($this->scheduled_start);
        $diff = $start - $now;

        if ($diff <= 0) {
            return Yii::t('JitsiMeetCloud8x8Module.base', 'Starting now');
        }

        if ($diff < 60) {
            return Yii::t('JitsiMeetCloud8x8Module.base', 'In {n} seconds', ['n' => $diff]);
        }
        if ($diff < 3600) {
            return Yii::t('JitsiMeetCloud8x8Module.base', 'In {n} minutes', ['n' => floor($diff / 60)]);
        }
        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            $mins = floor(($diff % 3600) / 60);
            return Yii::t('JitsiMeetCloud8x8Module.base', 'In {h}h {m}m', ['h' => $hours, 'm' => $mins]);
        }

        return Yii::$app->formatter->asRelativeTime($this->scheduled_start);
    }

    // =========================================================================
    // CalendarEventIF Implementation
    // =========================================================================

    /**
     * Get unique identifier for calendar
     * @return string
     */
    public function getUid(): string
    {
        return $this->uid ?: 'jitsi-' . $this->id;
    }

    /**
     * Whether this is an all-day event
     * @return bool
     */
    public function isAllDay(): bool
    {
        return (bool) $this->all_day;
    }

    /**
     * Get event start DateTime
     * @return DateTime|null
     */
    public function getStartDateTime(): ?DateTime
    {
        $dateStr = $this->scheduled_start ?: $this->start_time;
        if (empty($dateStr)) {
            return null;
        }
        return new DateTime($dateStr);
    }

    /**
     * Get event end DateTime
     * @return DateTime|null
     */
    public function getEndDateTime(): ?DateTime
    {
        $dateStr = $this->scheduled_end ?: $this->end_time;
        if (empty($dateStr)) {
            // Default to 1 hour after start
            $start = $this->getStartDateTime();
            if ($start) {
                return (clone $start)->modify('+1 hour');
            }
            return null;
        }
        return new DateTime($dateStr);
    }

    /**
     * Get timezone string
     * @return string
     */
    public function getTimezone(): string
    {
        // Stored timezone is the creator's profile timezone at time of scheduling.
        // Falls back to the current viewer's formatter timezone (from their profile).
        return $this->timezone ?: Yii::$app->formatter->timeZone;
    }

    /**
     * Get event URL (waiting room or join)
     * @return string
     */
    public function getUrl(): string
    {
        return Url::to(['/jitsi-meet-cloud-8x8/room/open', 'name' => $this->room_name], true);
    }

    /**
     * Get event title
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title ?: $this->room_name;
    }

    /**
     * Get event description
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description ?: '';
    }

    /**
     * Get last modified DateTime
     * @return DateTime
     */
    public function getLastModified(): DateTime
    {
        return new DateTime($this->updated_at);
    }

    // =========================================================================
    // Existing Methods
    // =========================================================================

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
     * Get parsed polls
     * @return array
     */
    public function getPolls()
    {
        if (empty($this->polls)) {
            return [];
        }
        $polls = json_decode($this->polls, true);
        return is_array($polls) ? $polls : [];
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

    /**
     * Get parsed feedback
     * @return array
     */
    public function getFeedback()
    {
        if (empty($this->feedback)) {
            return [];
        }
        $feedback = json_decode($this->feedback, true);
        return is_array($feedback) ? $feedback : [];
    }

    /**
     * Get thumbnail URL for the stream
     * @return string|null
     */
    /**
     * Get thumbnail URL for the stream
     * @return string|null
     */
    public function getThumbnailUrl()
    {
        // Currently 8x8 does not provide a direct thumbnail URL in the webhook metadata.
        // We return null so the video player uses its default behavior (first frame or black).
        return null;
    }

    /**
     * Get aggregated reaction counts
     * @return array ['👍' => 5, '❤️' => 2] sorted by count desc
     */
    public function getAggregatedReactions()
    {
        $reactions = $this->getReactions();
        if (empty($reactions)) {
            return [];
        }

        // Mapping textual keys to Emojis
        $emojiMap = [
            'like' => '👍',
            'clap' => '👏',
            'love' => '❤️',
            'surprised' => '😮',
            'laugh' => '😂',
            'fire' => '🔥',
            'boo' => '👎',
            'silence' => '😶',
            'raised_hand' => '✋',
        ];

        $aggregated = [];
        
        // Handle different potential structures
        foreach ($reactions as $r) {
            $emojiRaw = null;

            // Structure 1: Simple list of emoji strings
            if (is_string($r)) {
                $emojiRaw = $r;
            } 
            // Structure 2: Object with 'reaction' property
            elseif (is_array($r) && !empty($r['reaction'])) {
                $emojiRaw = $r['reaction'];
            }
            // Structure 3: Key-Value map of user->reaction
            elseif (is_array($r) && !empty($r['content'])) {
                 $emojiRaw = $r['content']; 
            }

            if ($emojiRaw) {
                $lower = strtolower($emojiRaw);
                $emoji = $emojiMap[$lower] ?? $emojiRaw; // Use map or fallback to raw (if it IS an emoji)

                if (!isset($aggregated[$emoji])) {
                    $aggregated[$emoji] = 0;
                }
                $aggregated[$emoji]++;
            }
        }

        // Sort by count descending
        arsort($aggregated);
        
        return $aggregated;
    }

    /**
     * Get recent participants for avatar display
     * @param int $limit
     * @return array List of ['user' => User|null, 'name' => string, 'email' => string]
     */
    public function getRecentParticipants($limit = 5)
    {
        $participants = [];

        // 1. SCHEDULED: Use Calendar Entry
        if ($this->status == self::STATUS_SCHEDULED) {
            if ($this->calendarEntry) {
                $attendeeIds = (new \yii\db\Query())
                    ->select(['user_id'])
                    ->from('calendar_entry_participant')
                    ->where(['calendar_entry_id' => $this->calendarEntry->id, 'participation_state' => 2]) // Accepted
                    ->limit($limit)
                    ->column();
                
                if (!empty($attendeeIds)) {
                     $users = User::find()->where(['id' => $attendeeIds])->all();
                     foreach ($users as $u) {
                         $participants[] = ['user' => $u, 'name' => $u->displayName, 'email' => $u->email];
                     }
                }
            }
        }
        // 2. LIVE: Use Cache
        elseif ($this->status == self::STATUS_LIVE) {
            $key = 'jitsiMeetCloud8x8:participants:' . $this->id;
            $cached = Yii::$app->cache->get($key);
            if (is_array($cached)) {
                // Reverse to show latest first
                $cached = array_reverse($cached);
                $count = 0;
                foreach ($cached as $pKey) {
                    if ($count >= $limit) break;
                    
                    // Parse Key: user_123, email_foo@bar.com, or raw ID
                    if (strpos($pKey, 'user_') === 0) {
                        $uid = substr($pKey, 5);
                        $u = User::findOne($uid);
                        if ($u) {
                            $participants[] = ['user' => $u, 'name' => $u->displayName, 'email' => $u->email];
                        }
                    } elseif (strpos($pKey, 'email_') === 0) {
                        $email = substr($pKey, 6);
                        $u = User::findOne(['email' => $email]);
                         if ($u) {
                            $participants[] = ['user' => $u, 'name' => $u->displayName, 'email' => $u->email];
                        } else {
                            $participants[] = ['user' => null, 'name' => $email, 'email' => $email];
                        }
                    } else {
                        // Raw ID - Guest
                         $participants[] = ['user' => null, 'name' => 'Guest', 'email' => ''];
                    }
                    $count++;
                }
            }
        }
        // 3. ENDED: Use Speaker Stats (Recorded in DB)
        elseif ($this->status == self::STATUS_ENDED) {
             if (!empty($this->speaker_stats)) {
                 $stats = json_decode($this->speaker_stats, true);
                 if (is_array($stats)) {
                     // 8x8 Stats format: {'id': {'displayName': '...', 'email': '...'}, ...}
                     foreach ($stats as $id => $data) {
                         if (count($participants) >= $limit) break;
                         
                         $name = $data['displayName'] ?? $data['name'] ?? 'Guest';
                         $email = $data['email'] ?? '';
                         
                         // Try to find user by email
                         $u = null;
                         if (!empty($email)) {
                             $u = User::findOne(['email' => $email]);
                         }
                         
                         // If no email match, try existing match for name? (Risky, skip)
                         
                         $participants[] = ['user' => $u, 'name' => $name, 'email' => $email];
                     }
                 }
             }
        }

        return $participants;
    }
}

<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\models;

use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;
use Yii;

/**
 * This is the model class for table "jitsi_instant_video_chat".
 *
 * @property int $id
 * @property string|null $title Optional custom title
 * @property string|null $description Optional custom description
 * @property string $room_name Generated or custom room name
 * @property int $created_by User who created the chat
 * @property int|null $space_id Space ID for space chats
 * @property int|null $user_id User ID for 1-on-1 chats
 * @property string $status Chat status (active, ended)
 * @property int $participant_count Number of participants
 * @property string|null $started_at When chat started
 * @property string|null $ended_at When chat ended
 * @property string $created_at Record creation time
 * @property string $updated_at Record update time
 *
 * @property User $createdBy
 * @property Space|null $space
 * @property User|null $user
 */
class InstantVideoChat extends ContentActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'jitsi_instant_video_chat';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['room_name', 'created_by'], 'required'],
            [['description'], 'string'],
            [['created_by', 'space_id', 'user_id', 'participant_count'], 'integer'],
            [['started_at', 'ended_at', 'created_at', 'updated_at'], 'safe'],
            [['title', 'room_name'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => ['active', 'ended']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
            [['space_id'], 'exist', 'skipOnError' => true, 'targetClass' => Space::class, 'targetAttribute' => ['space_id' => 'id']],
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
            'title' => Yii::t('JitsiMeetCloud8x8Module.base', 'Title'),
            'description' => Yii::t('JitsiMeetCloud8x8Module.base', 'Description'),
            'room_name' => Yii::t('JitsiMeetCloud8x8Module.base', 'Room Name'),
            'created_by' => Yii::t('JitsiMeetCloud8x8Module.base', 'Created By'),
            'space_id' => Yii::t('JitsiMeetCloud8x8Module.base', 'Space'),
            'user_id' => Yii::t('JitsiMeetCloud8x8Module.base', 'User'),
            'status' => Yii::t('JitsiMeetCloud8x8Module.base', 'Status'),
            'participant_count' => Yii::t('JitsiMeetCloud8x8Module.base', 'Participants'),
            'started_at' => Yii::t('JitsiMeetCloud8x8Module.base', 'Started At'),
            'ended_at' => Yii::t('JitsiMeetCloud8x8Module.base', 'Ended At'),
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
                $this->started_at = date('Y-m-d H:i:s');
                
                // Generate room name if title is empty
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
     * Generate room name based on context
     * @return string
     */
    private function generateRoomName()
    {
        $prefix = '';
        
        if ($this->space_id) {
            $space = Space::findOne($this->space_id);
            $prefix = $space ? $space->name : 'Space';
        } elseif ($this->user_id) {
            $user = User::findOne($this->user_id);
            $prefix = $user ? $user->displayName : 'User';
        } else {
            $prefix = 'Chat';
        }
        
        $timestamp = date('Ymd-His');
        return $prefix . '-' . $timestamp;
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
     * Gets query for [[Space]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSpace()
    {
        return $this->hasOne(Space::class, ['id' => 'space_id']);
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
     * Get the content container for this video chat
     * @return ContentContainerActiveRecord|null
     */
    public function getContentContainer()
    {
        if ($this->space_id) {
            return $this->space;
        } elseif ($this->user_id) {
            return $this->user;
        }
        return null;
    }

    /**
     * Check if the video chat is currently active
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if the video chat has ended
     * @return bool
     */
    public function isEnded()
    {
        return $this->status === 'ended';
    }

    /**
     * End the video chat
     * @return bool
     */
    public function endChat()
    {
        $this->status = 'ended';
        $this->ended_at = date('Y-m-d H:i:s');
        return $this->save();
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
     * Get the display title for this video chat
     * @return string
     */
    public function getDisplayTitle()
    {
        return $this->title ?: $this->room_name;
    }

    /**
     * Get the wall entry widget for this video chat
     * @return \humhubContrib\modules\jitsiMeetCloud8x8\widgets\WallEntryInstantChat
     */
    public function getWallOut()
    {
        return \humhubContrib\modules\jitsiMeetCloud8x8\widgets\WallEntryInstantChat::widget(['model' => $this]);
    }

    /**
     * Check if user can join this video chat
     * @param User|null $user
     * @return bool
     */
    public function canJoin($user = null)
    {
        if (!$user) {
            $user = Yii::$app->user->getIdentity();
        }
        
        if (!$user) {
            return false;
        }
        
        // Check if chat is active
        if (!$this->isActive()) {
            return false;
        }
        
        // Check permissions based on content container
        $container = $this->getContentContainer();
        if ($container) {
            return $container->can(\humhubContrib\modules\jitsiMeetCloud8x8\permissions\JoinVideoChat::class);
        }
        
        return false;
    }

    /**
     * Check if user can end this video chat
     * @param User|null $user
     * @return bool
     */
    public function canEnd($user = null)
    {
        if (!$user) {
            $user = Yii::$app->user->getIdentity();
        }
        
        if (!$user) {
            return false;
        }
        
        // Creator can always end
        if ($this->created_by === $user->id) {
            return true;
        }
        
        // Space admins can end space chats
        if ($this->space_id) {
            $space = $this->space;
            if ($space) {
                $membership = $space->getMembership($user);
                return $membership && ($membership->isAdmin() || $membership->isOwner());
            }
        }
        
        return false;
    }
}

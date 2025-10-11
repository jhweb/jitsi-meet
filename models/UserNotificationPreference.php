<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\models;

use Yii;

/**
 * Virtual model for user notification preferences (uses HumHub's user_setting table)
 *
 * This model provides a convenient interface for managing user notification preferences
 * without creating a separate table. It uses HumHub's built-in user_setting table
 * with module_id='jitsi-meet-cloud-8x8'.
 */
class UserNotificationPreference extends \yii\base\Model
{
    const NOTIFY_24H_BEFORE = 'notify_24h_before';
    const NOTIFY_5MIN_BEFORE = 'notify_5min_before';
    const NOTIFY_ON_START = 'notify_on_start';
    const CUSTOM_INTERVALS = 'custom_intervals';

    public $notify_24h_before = true;
    public $notify_5min_before = true;
    public $notify_on_start = false;
    public $custom_intervals = '';

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['notify_24h_before', 'notify_5min_before', 'notify_on_start'], 'boolean'],
            [['custom_intervals'], 'string'],
            [['custom_intervals'], 'validateCustomIntervals'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'notify_24h_before' => Yii::t('JitsiMeetCloud8x8Module.base', 'Notify 24 hours before scheduled video chats'),
            'notify_5min_before' => Yii::t('JitsiMeetCloud8x8Module.base', 'Notify 5 minutes before scheduled video chats'),
            'notify_on_start' => Yii::t('JitsiMeetCloud8x8Module.base', 'Notify when scheduled video chat starts'),
            'custom_intervals' => Yii::t('JitsiMeetCloud8x8Module.base', 'Custom notification intervals (comma-separated minutes)'),
        ];
    }

    /**
     * Validate custom intervals format
     * @param string $attribute
     * @param array $params
     */
    public function validateCustomIntervals($attribute, $params)
    {
        if (empty($this->$attribute)) {
            return;
        }
        
        $intervals = explode(',', $this->$attribute);
        foreach ($intervals as $interval) {
            $interval = trim($interval);
            if (!is_numeric($interval) || $interval < 1 || $interval > 10080) { // Max 1 week
                $this->addError($attribute, Yii::t('JitsiMeetCloud8x8Module.base', 'Custom intervals must be comma-separated numbers between 1 and 10080 minutes.'));
                return;
            }
        }
    }

    /**
     * Load user preferences from settings
     * @param int|null $userId
     * @return static
     */
    public static function load($userId = null)
    {
        if (!$userId) {
            $userId = Yii::$app->user->id;
        }
        
        if (!$userId) {
            return new static();
        }
        
        $module = Yii::$app->getModule('jitsi-meet-cloud-8x8');
        $settings = $module->settings->user($userId);
        
        $model = new static();
        $model->notify_24h_before = (bool)$settings->get(self::NOTIFY_24H_BEFORE, true);
        $model->notify_5min_before = (bool)$settings->get(self::NOTIFY_5MIN_BEFORE, true);
        $model->notify_on_start = (bool)$settings->get(self::NOTIFY_ON_START, false);
        $model->custom_intervals = (string)$settings->get(self::CUSTOM_INTERVALS, '');
        
        return $model;
    }

    /**
     * Save user preferences to settings
     * @param int|null $userId
     * @return bool
     */
    public function save($userId = null)
    {
        if (!$this->validate()) {
            return false;
        }
        
        if (!$userId) {
            $userId = Yii::$app->user->id;
        }
        
        if (!$userId) {
            return false;
        }
        
        $module = Yii::$app->getModule('jitsi-meet-cloud-8x8');
        $settings = $module->settings->user($userId);
        
        $settings->set(self::NOTIFY_24H_BEFORE, $this->notify_24h_before);
        $settings->set(self::NOTIFY_5MIN_BEFORE, $this->notify_5min_before);
        $settings->set(self::NOTIFY_ON_START, $this->notify_on_start);
        $settings->set(self::CUSTOM_INTERVALS, $this->custom_intervals);
        
        return true;
    }

    /**
     * Get all notification intervals for this user
     * @return array
     */
    public function getNotificationIntervals()
    {
        $intervals = [];
        
        if ($this->notify_24h_before) {
            $intervals[] = 1440; // 24 hours
        }
        
        if ($this->notify_5min_before) {
            $intervals[] = 5; // 5 minutes
        }
        
        if ($this->notify_on_start) {
            $intervals[] = 0; // On start
        }
        
        // Add custom intervals
        if (!empty($this->custom_intervals)) {
            $customIntervals = explode(',', $this->custom_intervals);
            foreach ($customIntervals as $interval) {
                $interval = (int)trim($interval);
                if ($interval > 0 && !in_array($interval, $intervals)) {
                    $intervals[] = $interval;
                }
            }
        }
        
        sort($intervals);
        return $intervals;
    }

    /**
     * Check if user wants notifications for a specific interval
     * @param int $intervalMinutes
     * @return bool
     */
    public function wantsNotificationForInterval($intervalMinutes)
    {
        $intervals = $this->getNotificationIntervals();
        return in_array($intervalMinutes, $intervals);
    }

    /**
     * Get default preferences for new users
     * @return array
     */
    public static function getDefaults()
    {
        return [
            self::NOTIFY_24H_BEFORE => true,
            self::NOTIFY_5MIN_BEFORE => true,
            self::NOTIFY_ON_START => false,
            self::CUSTOM_INTERVALS => '',
        ];
    }

    /**
     * Reset user preferences to defaults
     * @param int|null $userId
     * @return bool
     */
    public static function resetToDefaults($userId = null)
    {
        if (!$userId) {
            $userId = Yii::$app->user->id;
        }
        
        if (!$userId) {
            return false;
        }
        
        $module = Yii::$app->getModule('jitsi-meet-cloud-8x8');
        $settings = $module->settings->user($userId);
        
        $defaults = self::getDefaults();
        foreach ($defaults as $key => $value) {
            $settings->set($key, $value);
        }
        
        return true;
    }
}


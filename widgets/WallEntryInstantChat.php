<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\widgets;

use humhubContrib\modules\jitsiMeetCloud8x8\models\InstantVideoChat;
use humhub\widgets\Button;
use humhub\widgets\Label;
use yii\helpers\Html;
use yii\helpers\Url;
use Yii;

/**
 * WallEntryInstantChat widget for instant video chats
 * 
 * Renders instant video chats in the space stream with join button,
 * participant count, live indicator, and end chat functionality.
 */
class WallEntryInstantChat extends \humhub\widgets\BaseStack
{
    /**
     * @var InstantVideoChat
     */
    public $model;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        
        if (!$this->model instanceof InstantVideoChat) {
            throw new \InvalidArgumentException('Model must be an instance of InstantVideoChat');
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        return $this->render('wall-entry-instant', [
            'model' => $this->model
        ]);
    }

    /**
     * Get the join button
     * @return string
     */
    public function getJoinButton()
    {
        if (!$this->model->isActive()) {
            return '';
        }

        return Button::asLink(Yii::t('JitsiMeetCloud8x8Module.base', 'Join Now'))
            ->icon('video-camera')
            ->cssClass('btn btn-primary btn-sm')
            ->link($this->model->getJoinUrl())
            ->options([
                'target' => '_blank',
                'title' => Yii::t('JitsiMeetCloud8x8Module.base', 'Join the video chat')
            ]);
    }

    /**
     * Get the end chat button
     * @return string
     */
    public function getEndButton()
    {
        if (!$this->model->canEnd()) {
            return '';
        }

        return Button::asLink(Yii::t('JitsiMeetCloud8x8Module.base', 'End Chat'))
            ->icon('stop')
            ->cssClass('btn btn-danger btn-sm')
            ->action('jitsi-meet-cloud-8x8.space.endChat', ['id' => $this->model->id])
            ->options([
                'title' => Yii::t('JitsiMeetCloud8x8Module.base', 'End this video chat'),
                'data-confirm' => Yii::t('JitsiMeetCloud8x8Module.base', 'Are you sure you want to end this video chat?')
            ]);
    }

    /**
     * Get the live indicator
     * @return string
     */
    public function getLiveIndicator()
    {
        if (!$this->model->isActive()) {
            return '';
        }

        return Label::asSuccess(Yii::t('JitsiMeetCloud8x8Module.base', 'LIVE'))
            ->cssClass('label-live');
    }

    /**
     * Get the participant count
     * @return string
     */
    public function getParticipantCount()
    {
        if ($this->model->participant_count <= 0) {
            return '';
        }

        return Html::tag('span', 
            Html::icon('users') . ' ' . $this->model->participant_count,
            ['class' => 'participant-count']
        );
    }

    /**
     * Get the time started
     * @return string
     */
    public function getTimeStarted()
    {
        if (!$this->model->started_at) {
            return '';
        }

        $time = Yii::$app->formatter->asRelativeTime($this->model->started_at);
        return Html::tag('span', 
            Html::icon('clock-o') . ' ' . $time,
            ['class' => 'time-started']
        );
    }
}

<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\widgets;

use humhub\modules\content\components\ContentContainerActiveRecord;
use humhubContrib\modules\jitsiMeetCloud8x8\permissions\CreateVideoChat;
use humhub\widgets\Button;
use Yii;

/**
 * QuickVideoChatButton widget for space stream composer
 * 
 * Adds a "Start Video Chat" button to the space stream composer
 * that opens a modal for creating instant video chats.
 */
class QuickVideoChatButton extends \humhub\widgets\BaseStack
{
    /**
     * @var ContentContainerActiveRecord
     */
    public $contentContainer;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        
        if (!$this->contentContainer) {
            $this->contentContainer = Yii::$app->controller->contentContainer ?? null;
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        // Only show if user has permission to create video chats
        if (!$this->contentContainer || !$this->contentContainer->can(CreateVideoChat::class)) {
            return '';
        }

        // Only show in space context (not user profiles for now)
        if (!($this->contentContainer instanceof \humhub\modules\space\models\Space)) {
            return '';
        }

        return $this->render('quick-video-chat-button', [
            'contentContainer' => $this->contentContainer
        ]);
    }

    /**
     * Get the button HTML
     * @return string
     */
    public function getButton()
    {
        return Button::asLink(Yii::t('JitsiMeetCloud8x8Module.base', 'Start Video Chat'))
            ->icon('video-camera')
            ->cssClass('btn btn-primary')
            ->action('jitsi-meet-cloud-8x8.space.quickPostModal')
            ->options([
                'data-toggle' => 'modal',
                'data-target' => '#quick-video-chat-modal',
                'title' => Yii::t('JitsiMeetCloud8x8Module.base', 'Start an instant video chat')
            ]);
    }

    /**
     * Get the modal HTML
     * @return string
     */
    public function getModal()
    {
        return $this->render('quick-video-chat-modal', [
            'contentContainer' => $this->contentContainer
        ]);
    }
}

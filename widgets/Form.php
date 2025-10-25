<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 The Oil Press
 * @license https://www.humhub.com/licences
 */

namespace humhubContrib\modules\jitsiMeetCloud8x8\widgets;

use humhub\modules\content\widgets\WallCreateContentForm;
use humhubContrib\modules\jitsiMeetCloud8x8\models\InstantVideoChat;
use humhub\modules\ui\form\widgets\ActiveForm;
use yii\helpers\Url;

/**
 * This widget is used to include the video chat form.
 * It normally should be placed above a stream.
 */
class Form extends WallCreateContentForm
{

    /**
     * Get params for form rendering
     *
     * @param array $additionalParams
     * @return array
     */
    public function getRenderParams(array $additionalParams = []): array
    {
        $videoChat = new InstantVideoChat();
        $videoChat->content->container = $this->contentContainer;

        return array_merge([
            'videoChat' => $videoChat,
            'wallCreateContentForm' => $this,
        ], $additionalParams);
    }

    /**
     * @inheritdoc
     */
    public function renderForm(): string
    {
        return $this->render('@humhubContrib/modules/jitsiMeetCloud8x8/widgets/views/form', $this->getRenderParams());
    }

    /**
     * @inheritdoc
     */
    public function renderActiveForm(ActiveForm $form): string
    {
        return $this->render('@humhubContrib/modules/jitsiMeetCloud8x8/widgets/views/form', $this->getRenderParams(['form' => $form]));
    }

    /**
     * @inheritdoc
     */
    public function getSubmitUrl()
    {
        if ($this->contentContainer) {
            return $this->contentContainer->createUrl('/jitsi-meet-cloud-8x8/instant-video-chat/create');
        }
        return '#';
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $videoChat = new InstantVideoChat();
        $videoChat->content->container = $this->contentContainer;
        
        if (!$videoChat->content->canEdit()) {
            return '';
        }

        return parent::run();
    }
}

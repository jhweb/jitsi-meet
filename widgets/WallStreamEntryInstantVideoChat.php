<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2025 The Oil Press
 * @license https://www.humhub.com/licences
 */

namespace humhubContrib\modules\jitsiMeetCloud8x8\widgets;

use humhub\modules\content\widgets\stream\WallStreamEntryWidget;
use humhubContrib\modules\jitsiMeetCloud8x8\models\InstantVideoChat;

/**
 * WallStreamEntryWidget for InstantVideoChat content
 */
class WallStreamEntryInstantVideoChat extends WallStreamEntryWidget
{
    /**
     * Route to create a new video chat
     * @var string
     */
    public $createRoute = '/jitsi-meet-cloud-8x8/space/create-form';

    /**
     * Create mode - use modal for video chat creation
     * @var string
     */
    public $createMode = WallStreamEntryWidget::EDIT_MODE_MODAL;

    /**
     * Sort order for the create form menu (lower numbers appear first)
     * @var int
     */
    public $createFormSortOrder = 200;

    /**
     * Action for the create form menu
     * @var string
     */
    public $createFormMenuAction = 'ui.modal.load';

    /**
     * @inheritdoc
     */
    protected function renderContent()
    {
        return $this->render('wallStreamEntryInstantVideoChat', [
            'model' => $this->model,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->model->getDisplayTitle();
    }

    /**
     * @inheritdoc
     */
    public function getIcon()
    {
        return 'video-camera';
    }
}

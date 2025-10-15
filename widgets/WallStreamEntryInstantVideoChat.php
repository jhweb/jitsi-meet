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
     * Route to create a content
     *
     * @var string
     */
    public $createRoute = '/jitsi-meet-cloud-8x8/space/create-form';

    /**
     * @inheritdoc
     */
    public $createMode = self::EDIT_MODE_INLINE;

    /**
     * @inheritdoc
     */
    public $createFormSortOrder = 100;

    /**
     * @inheritdoc
     */
    public $createFormClass = Form::class;

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

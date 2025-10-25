<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\controllers;

use humhub\modules\content\components\ContentContainerController;
use humhubContrib\modules\jitsiMeetCloud8x8\models\InstantVideoChat;
use humhubContrib\modules\jitsiMeetCloud8x8\permissions\CreateVideoChat;
use humhubContrib\modules\jitsiMeetCloud8x8\widgets\Form;
use Yii;

/**
 * InstantVideoChatController handles video chat creation from wall stream composer
 */
class InstantVideoChatController extends ContentContainerController
{
    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [
            ['permissions' => [CreateVideoChat::class], 'actions' => ['create-form', 'create']]
        ];
    }

    /**
     * Render the create form for the wall stream composer
     * @return string
     */
    public function actionCreateForm()
    {
        $videoChat = new InstantVideoChat();
        $videoChat->content->container = $this->contentContainer;
        
        if (!$videoChat->content->canEdit()) {
            throw new \yii\web\ForbiddenHttpException();
        }

        return $this->renderAjaxPartial(Form::widget([
            'contentContainer' => $this->contentContainer,
        ]));
    }

    /**
     * Create instant video chat from wall stream composer
     * @return array|string
     */
    public function actionCreate()
    {
        // Check permissions
        if (!$this->contentContainer->can(CreateVideoChat::class)) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = 'json';
                return ['error' => Yii::t('JitsiMeetCloud8x8Module.base', 'You do not have permission to create video chats in this space.')];
            }
            Yii::$app->session->setFlash('error', Yii::t('JitsiMeetCloud8x8Module.base', 'You do not have permission to create video chats in this space.'));
            return $this->redirect($this->contentContainer->createUrl('/space/space'));
        }

        // Create new video chat model
        $videoChat = new InstantVideoChat();
        $videoChat->content->container = $this->contentContainer;

        // Load form data
        if ($videoChat->load(Yii::$app->request->post()) && $videoChat->save()) {
            // Send notification to space members
            $this->sendInstantChatNotification($videoChat);
            
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = 'json';
                return [
                    'success' => true,
                    'message' => Yii::t('JitsiMeetCloud8x8Module.base', 'Video chat started successfully!'),
                    'joinUrl' => $videoChat->getJoinUrl(),
                    'wallEntry' => $videoChat->getWallOut()
                ];
            }
            
            Yii::$app->session->setFlash('success', Yii::t('JitsiMeetCloud8x8Module.base', 'Video chat started successfully!'));
            return $this->redirect($videoChat->getJoinUrl());
        } else {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = 'json';
                return [
                    'error' => Yii::t('JitsiMeetCloud8x8Module.base', 'Failed to create video chat.'),
                    'errors' => $videoChat->getErrors()
                ];
            }
            
            Yii::$app->session->setFlash('error', Yii::t('JitsiMeetCloud8x8Module.base', 'Failed to create video chat.'));
            return $this->redirect($this->contentContainer->createUrl('/space/space'));
        }
    }

    /**
     * Send notification to space members about instant video chat
     * @param InstantVideoChat $videoChat
     */
    private function sendInstantChatNotification($videoChat)
    {
        if (!$videoChat->space_id) {
            return; // Only notify for space chats
        }

        $space = $videoChat->space;
        if (!$space) {
            return;
        }

        // Get all space members
        $members = $space->getMemberships()->with('user')->all();
        
        foreach ($members as $membership) {
            if ($membership->user && $membership->user->id !== $videoChat->created_by) {
                $notification = new \humhubContrib\modules\jitsiMeetCloud8x8\notifications\InstantVideoChatStartedNotification();
                $notification->source = $videoChat;
                $notification->originator = $videoChat->createdBy;
                $notification->send($membership->user);
            }
        }
    }
}

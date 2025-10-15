<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\controllers;

use humhub\components\Controller;
use humhub\modules\content\components\ContentContainerController;
use humhubContrib\modules\jitsiMeetCloud8x8\models\InstantVideoChat;
use humhubContrib\modules\jitsiMeetCloud8x8\permissions\CreateVideoChat;
use humhubContrib\modules\jitsiMeetCloud8x8\permissions\JoinVideoChat;
use Yii;

/**
 * SpaceController handles video chat actions within Space context
 */
class SpaceController extends ContentContainerController
{
    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [
            ['permissions' => [JoinVideoChat::class], 'actions' => ['quick-post-modal', 'create-form']],
            ['permissions' => [CreateVideoChat::class], 'actions' => ['quick-post', 'end-chat']]
        ];
    }

    /**
     * Show quick post modal form
     * @return string
     */
    public function actionQuickPostModal()
    {
        if (!Yii::$app->request->isAjax) {
            return $this->redirect(['/jitsi-meet-cloud-8x8/room']);
        }

        return $this->renderAjax('quick-post-modal', [
            'contentContainer' => $this->contentContainer
        ]);
    }

    /**
     * Create form for inline display
     * @return string
     */
    public function actionCreateForm()
    {
        $videoChat = new InstantVideoChat();
        $videoChat->content->container = $this->contentContainer;
        
        if (!$videoChat->content->canEdit()) {
            throw new \yii\web\ForbiddenHttpException();
        }

        return $this->renderAjaxPartial(\humhubContrib\modules\jitsiMeetCloud8x8\widgets\Form::widget([
            'contentContainer' => $this->contentContainer,
        ]));
    }

    /**
     * Create instant video chat and post to space stream
     * @return array|string
     */
    public function actionQuickPost()
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

        $title = Yii::$app->request->post('title', '');
        $description = Yii::$app->request->post('description', '');

        // Create instant video chat
        $videoChat = new InstantVideoChat();
        $videoChat->title = $title;
        $videoChat->description = $description;
        
        // Set content container
        if ($this->contentContainer instanceof \humhub\modules\space\models\Space) {
            $videoChat->space_id = $this->contentContainer->id;
        } elseif ($this->contentContainer instanceof \humhub\modules\user\models\User) {
            $videoChat->user_id = $this->contentContainer->id;
        }

        // Set content properties
        $videoChat->content->container = $this->contentContainer;
        $videoChat->content->visibility = \humhub\modules\content\models\Content::VISIBILITY_PRIVATE;

        if ($videoChat->save()) {
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
     * End an instant video chat
     * @param int $id
     * @return array
     */
    public function actionEndChat($id)
    {
        Yii::$app->response->format = 'json';

        $videoChat = InstantVideoChat::findOne($id);
        if (!$videoChat) {
            return ['error' => Yii::t('JitsiMeetCloud8x8Module.base', 'Video chat not found.')];
        }

        // Check permissions
        if (!$videoChat->canEnd()) {
            return ['error' => Yii::t('JitsiMeetCloud8x8Module.base', 'You do not have permission to end this video chat.')];
        }

        if ($videoChat->endChat()) {
            return [
                'success' => true,
                'message' => Yii::t('JitsiMeetCloud8x8Module.base', 'Video chat ended successfully.')
            ];
        } else {
            return [
                'error' => Yii::t('JitsiMeetCloud8x8Module.base', 'Failed to end video chat.')
            ];
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

    /**
     * Get active video chats in this space
     * @return array
     */
    public function actionActiveChats()
    {
        Yii::$app->response->format = 'json';

        $query = InstantVideoChat::find()
            ->where(['status' => 'active'])
            ->orderBy(['started_at' => SORT_DESC]);

        if ($this->contentContainer instanceof \humhub\modules\space\models\Space) {
            $query->andWhere(['space_id' => $this->contentContainer->id]);
        } elseif ($this->contentContainer instanceof \humhub\modules\user\models\User) {
            $query->andWhere(['user_id' => $this->contentContainer->id]);
        }

        $chats = $query->all();
        $result = [];

        foreach ($chats as $chat) {
            $result[] = [
                'id' => $chat->id,
                'title' => $chat->getDisplayTitle(),
                'description' => $chat->description,
                'joinUrl' => $chat->getJoinUrl(),
                'participantCount' => $chat->participant_count,
                'startedAt' => $chat->started_at,
                'canJoin' => $chat->canJoin(),
                'canEnd' => $chat->canEnd(),
                'creator' => $chat->createdBy->displayName
            ];
        }

        return $result;
    }
}


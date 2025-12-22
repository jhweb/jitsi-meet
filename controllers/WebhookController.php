<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\controllers;

use humhub\components\Controller;
use humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream;
use humhub\modules\user\models\User;
use Yii;
use yii\web\Response;

class WebhookController extends Controller
{
    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [
            ['actions' => ['index'], 'users' => ['*']]
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if ($action->id === 'index') {
            // Disable CSRF for webhook
            $this->enableCsrfValidation = false;
        }

        // Bypass parent beforeAction for index to avoid potential auth redirects
        if ($action->id === 'index') {
            return true; 
        }

        return parent::beforeAction($action);
    }

    /**
     * Handle incoming webhooks from 8x8 JaaS
     */
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $rawBody = Yii::$app->request->getRawBody();
        $payload = json_decode($rawBody, true);

        if (!$payload || !isset($payload['eventType'])) {
            return ['status' => 'error', 'message' => 'Invalid payload'];
        }

        $eventType = $payload['eventType'];
        $fqn = $payload['fqn'] ?? '';
        
        // Extract room name from FQN (AppID/RoomName)
        $parts = explode('/', $fqn);
        $roomName = end($parts);
        
        Yii::info("Jitsi Webhook received: $eventType for room: $roomName", 'jitsi-meet-cloud-8x8');

        switch ($eventType) {
            case 'ROOM_CREATED':
                // Treat room creation as the start of a "live stream" session
                $this->handleRoomCreated($roomName, $payload);
                break;
            case 'ROOM_DESTROYED':
                $this->handleRoomDestroyed($roomName, $payload);
                break;
            case 'RECORDING_UPLOADED':
                $this->handleRecordingUploaded($roomName, $payload);
                break;
            case 'LIVE_STREAM_STARTED':
                // Optional: You could allow dual triggers, but usually ROOM_CREATED is the master event for "meeting started"
                // $this->handleLiveStreamStarted($roomName, $payload); 
                break;
            case 'LIVE_STREAM_ENDED':
                 // $this->handleLiveStreamEnded($roomName, $payload);
                 break;
            case 'PARTICIPANT_JOINED':
                $this->handleParticipantJoined($roomName, $payload);
                break;
            case 'PARTICIPANT_LEFT':
                $this->handleParticipantLeft($roomName, $payload);
                break;
        }

        return ['status' => 'success'];
    }

    private function handleRoomCreated($roomName, $payload)
    {
        $sessionId = $payload['sessionId'] ?? null;
        
        // Try to find by session ID first
        $stream = null;
        if ($sessionId) {
            $stream = JitsiLiveStream::findOne(['session_id' => $sessionId]);
        }
        
        if (!$stream) {
            // Check for an existing active stream for this room to avoid duplicates
            $stream = JitsiLiveStream::findOne(['room_name' => $roomName, 'status' => JitsiLiveStream::STATUS_LIVE]);
        }
        
        if (!$stream) {
            $stream = new JitsiLiveStream();
            $stream->room_name = $roomName;
            $stream->session_id = $sessionId;
        }

        $stream->status = JitsiLiveStream::STATUS_LIVE;
        $stream->start_time = date('Y-m-d H:i:s', $payload['timestamp'] / 1000);
        $stream->event_id = $payload['idempotencyKey'] ?? null;
        
        // Try to identify creator from cache
        $cache = Yii::$app->cache;
        if ($cache !== null) {
            $creatorId = $cache->get('jitsiMeetCloud8x8:roomCreator:' . $roomName);
            if ($creatorId) {
                $stream->creator_id = $creatorId;
            }
        }
        
        $stream->save();
    }


    private function handleRoomDestroyed($roomName, $payload)
    {
        $sessionId = $payload['sessionId'] ?? null;
        $stream = null;

        if ($sessionId) {
            $stream = JitsiLiveStream::findOne(['session_id' => $sessionId]);
        }

        if (!$stream) {
             $stream = JitsiLiveStream::findOne(['room_name' => $roomName, 'status' => JitsiLiveStream::STATUS_LIVE]);
        }

        if ($stream && $stream->status == JitsiLiveStream::STATUS_LIVE) {
            $stream->status = JitsiLiveStream::STATUS_ENDED;
            $stream->end_time = date('Y-m-d H:i:s', $payload['timestamp'] / 1000);
            $stream->save();
        }
    }

    private function handleRecordingUploaded($roomName, $payload)
    {
        $sessionId = $payload['sessionId'] ?? null;
        $data = $payload['data'] ?? [];
        $recordingLink = $data['preAuthenticatedLink'] ?? null;

        if (!$recordingLink) {
            return;
        }

        // Find the stream by session ID
        $stream = null;
        if ($sessionId) {
             $stream = JitsiLiveStream::findOne(['session_id' => $sessionId]);
        }

        // Fallback: find most recent ended stream for this room
        if (!$stream) {
             $stream = JitsiLiveStream::find()
                ->where(['room_name' => $roomName])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();
        }

        if ($stream) {
            $stream->recording_url = $recordingLink;
            $stream->save();
        }
    }

    private function handleParticipantJoined($roomName, $payload)
    {
        $sessionId = $payload['sessionId'] ?? null;
        $stream = null;

        if ($sessionId) {
            $stream = JitsiLiveStream::findOne(['session_id' => $sessionId]);
        }

        if (!$stream) {
             $stream = JitsiLiveStream::findOne(['room_name' => $roomName, 'status' => JitsiLiveStream::STATUS_LIVE]);
        }

        if ($stream) {
            $stream->updateCounters(['participant_count' => 1]);
        }
    }

    private function handleParticipantLeft($roomName, $payload)
    {
        $sessionId = $payload['sessionId'] ?? null;
        $stream = null;

        if ($sessionId) {
            $stream = JitsiLiveStream::findOne(['session_id' => $sessionId]);
        }

        if (!$stream) {
             $stream = JitsiLiveStream::findOne(['room_name' => $roomName, 'status' => JitsiLiveStream::STATUS_LIVE]);
        }

        if ($stream && $stream->participant_count > 0) {
            $stream->updateCounters(['participant_count' => -1]);
        }
    }
}

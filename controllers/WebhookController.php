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
    protected $access = Controller::ACCESS_PUBLIC;

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
            case 'LIVE_STREAM_STARTED':
                $this->handleLiveStreamStarted($roomName, $payload);
                break;
            case 'LIVE_STREAM_ENDED':
                $this->handleLiveStreamEnded($roomName, $payload);
                break;
            case 'ROOM_DESTROYED':
                $this->handleRoomDestroyed($roomName, $payload);
                break;
            case 'RECORDING_UPLOADED':
                $this->handleRecordingUploaded($roomName, $payload);
                break;
        }

        return ['status' => 'success'];
    }

    private function handleLiveStreamStarted($roomName, $payload)
    {
        $sessionId = $payload['sessionId'] ?? null;
        
        // Try to find by session ID first, then room name (if active)
        $stream = null;
        if ($sessionId) {
            $stream = JitsiLiveStream::findOne(['session_id' => $sessionId]);
        }
        
        if (!$stream) {
            // Check for an existing active stream for this room to update, or create new
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

    private function handleLiveStreamEnded($roomName, $payload)
    {
        $sessionId = $payload['sessionId'] ?? null;
        $stream = null;
        
        if ($sessionId) {
            $stream = JitsiLiveStream::findOne(['session_id' => $sessionId]);
        }
        
        // Fallback to finding active stream by name
        if (!$stream) {
             $stream = JitsiLiveStream::findOne(['room_name' => $roomName, 'status' => JitsiLiveStream::STATUS_LIVE]);
        }

        if ($stream) {
            $stream->status = JitsiLiveStream::STATUS_ENDED;
            $stream->end_time = date('Y-m-d H:i:s', $payload['timestamp'] / 1000);
            $stream->save();
        }
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
}

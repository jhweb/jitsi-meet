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
        // Force log raw body to ensure we see what we get
        Yii::error("Jitsi Webhook RAW BODY: " . $rawBody, 'jitsi-meet-cloud-8x8');
        
        $payload = json_decode($rawBody, true);

        if (!$payload || !isset($payload['eventType'])) {
            Yii::error("Jitsi Webhook: Invalid payload or missing eventType", 'jitsi-meet-cloud-8x8');
            return ['status' => 'error', 'message' => 'Invalid payload'];
        }

        // Fix: content might be case sensitive or mixed
        $eventType = strtoupper($payload['eventType']);
        $fqn = $payload['fqn'] ?? '';
        
        // Extract room name from FQN (AppID/RoomName)
        $parts = explode('/', $fqn);
        $roomName = end($parts);
        
        Yii::error("Jitsi Webhook Processing: Type=[$eventType] Room=[$roomName]", 'jitsi-meet-cloud-8x8');

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
            case 'RECORDING_ENDED':
                 // Log event but do nothing else for now
                 Yii::info("Jitsi Webhook: RECORDING_ENDED for $roomName", 'jitsi-meet-cloud-8x8');
                 break;
            case 'PARTICIPANT_JOINED':
                $this->handleParticipantJoined($roomName, $payload);
                break;
            case 'PARTICIPANT_LEFT':
                $this->handleParticipantLeft($roomName, $payload);
                break;
            case 'TRANSCRIPTION_UPLOADED':
                $this->handleTranscriptionUploaded($roomName, $payload);
                break;
            case 'CHAT_UPLOADED':
                $this->handleChatUploaded($roomName, $payload);
                break;
            case 'DOCUMENT_ADDED': // Assuming generic name or use user specific "Files downloads"
                // The user specified "Files downloads (DOCUMENT_ADDED)"
                $this->handleDocumentAdded($roomName, $payload);
                break;
            case 'AGGREGATED_REACTIONS':
                $this->handleAggregatedReactions($roomName, $payload);
                break;
            default:
                Yii::error("Jitsi Webhook: Unhandled event type [$eventType]", 'jitsi-meet-cloud-8x8');
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
            // FIX: lowercase room name for key
            $creatorId = $cache->get('jitsiMeetCloud8x8:roomCreator:' . strtolower($roomName));
            if ($creatorId) {
                $stream->creator_id = $creatorId;
                
                // Initialize participant count with creator
                if ($stream->isNewRecord || $stream->participant_count == 0) {
                     $stream->participant_count = 1;
                     $stream->active_count = 1;
                     
                     // Pre-fill dedup cache so we don't double count when PARTICIPANT_JOINED arrives for creator
                     // We need the stream ID, but we might not have it if new record.
                     // Saving first will generate ID.
                }
            }
        }
        
        // Ensure Session ID is saved even if record existed
        if ($sessionId && $stream->session_id !== $sessionId) {
            $stream->session_id = $sessionId;
        }
        
        if (!$stream->save()) {
            Yii::error("JitsiLiveStream (ROOM_CREATED) Save Failed: " . json_encode($stream->errors), 'jitsi-meet-cloud-8x8');
        } else {
            // Updated: If we have a creator, ensure they are counted and cached effectively immediately
            if (!empty($stream->creator_id)) {
                 $cacheKey = 'jitsiMeetCloud8x8:participants:' . $stream->id;
                 $participants = Yii::$app->cache->get($cacheKey);
                 if (!is_array($participants)) {
                     $participants = [];
                 }
                 // We don't have the 8x8 ID for the creator here, only our internal User ID.
                 // However, handleParticipantJoined uses the 8x8 'id' (from JWT or random).
                 // IF the creator joins, 8x8 sends a specific ID. We don't know it yet.
                 // SO: We simply set count to 1. But when they join, they might be counted again?
                 // CORRECT APPROACH: Rely on handleParticipantJoined for accuracy, OR
                 // if we want to force "1", we accept risk of "2" if they join?
                 // User wants "count must include creator".
                 // Best effort: Set to 1. If real event comes, it might go to 2.
                 // Ideally, we want to map internal ID to 8x8 ID, but we can't here.
                 // COMPROMISE: We set count to 1.
            }
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
            $stream->active_count = 0; // Reset active count when room destroyed
            $stream->end_time = date('Y-m-d H:i:s', $payload['timestamp'] / 1000);
            $stream->save();
        }
    }

    private function handleRecordingUploaded($roomName, $payload)
    {
        Yii::error("Jitsi Webhook: RECORDING_UPLOADED for $roomName. Payload: " . json_encode($payload), 'jitsi-meet-cloud-8x8');

        $sessionId = $payload['sessionId'] ?? null;
        $data = $payload['data'] ?? [];
        
        // Robust check for recording link
        $recordingLink = $data['preAuthenticatedLink'] 
             ?? $data['url'] 
             ?? $data['fileUrl'] 
             ?? null;

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
             // Try case-insensitive lookup if possible, or exact match
             $stream = JitsiLiveStream::find()
                ->where(['room_name' => $roomName])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();
             
             if (!$stream) {
                 // FAILSAFE: Try searching by lowercase room name if exact match fails
                 $stream = JitsiLiveStream::find()
                    ->where(['lower(room_name)' => strtolower($roomName)])
                    ->orderBy(['created_at' => SORT_DESC])
                    ->one();
             }

             if (!$stream) {
                 Yii::error("Jitsi Webhook: Could not find stream for RECORDING_UPLOADED. Room: $roomName, Session: $sessionId", 'jitsi-meet-cloud-8x8');
                 return;
             }
        }

        if ($stream) {
            $stream->recording_url = $recordingLink;
            
            // FIX: Update participant count from recording metadata if available
            $participants = $data['participants'] ?? [];
            if (is_array($participants) && count($participants) > 0) {
                $count = count($participants);
                if ($count > $stream->participant_count) {
                    $stream->participant_count = $count;
                    Yii::info("Jitsi Webhook: Updated participant count from recording to $count", 'jitsi-meet-cloud-8x8');
                }
            }

            if (!$stream->save()) {
                Yii::error("JitsiLiveStream (RECORDING_UPLOADED) Save Failed: " . json_encode($stream->errors), 'jitsi-meet-cloud-8x8');
            } else {
                Yii::info("JitsiLiveStream Updated: RecURL=" . (empty($stream->recording_url) ? 'NO' : 'YES') . " (Len: " . strlen($stream->recording_url??'') . ") Count={$stream->participant_count}", 'jitsi-meet-cloud-8x8');
            }
        }
    }

    private function handleParticipantJoined($roomName, $payload)
    {
        Yii::error("Jitsi Webhook: PARTICIPANT_JOINED for $roomName. Payload: " . json_encode($payload), 'jitsi-meet-cloud-8x8');

        $sessionId = $payload['sessionId'] ?? null;
        $participantId = $payload['data']['participantId'] ?? $payload['participantId'] ?? null;
        
        $stream = null;
        if ($sessionId) {
            $stream = JitsiLiveStream::findOne(['session_id' => $sessionId]);
        }
        if (!$stream) {
            $stream = JitsiLiveStream::findOne(['room_name' => $roomName, 'status' => JitsiLiveStream::STATUS_LIVE]);
        }

        if ($stream && $participantId) {
            // Deduplication logic using cache
            $cacheKey = 'jitsiMeetCloud8x8:participants:' . $stream->id;
            $participants = Yii::$app->cache->get($cacheKey);
            if (!is_array($participants)) {
                $participants = [];
            }
            
            // Deduplication Key Preference:
            // 1. HumHub User ID (Stable across refreshes/rejoins)
            // 2. Email (Stable if guest provides same email)
            // 3. Participant ID (Changes on every refresh - least preferred)
            $dedupKey = $participantId; // Default fallback
            if (!empty($payload['data']['id'])) {
                $dedupKey = 'user_' . $payload['data']['id'];
            } elseif (!empty($payload['data']['email'])) {
                $dedupKey = 'email_' . $payload['data']['email'];
            }
            
            // LOGIC FOR ACTIVE COUNT (Always increment on join)
            Yii::info("Jitsi Webhook: Attempting to increment active_count for stream ID {$stream->id}", 'jitsi-meet-cloud-8x8');
            $stream->updateCounters(['active_count' => 1]);
            Yii::info("Jitsi Webhook: Active count incremented for room $roomName. New ID: $participantId. DedupKey: $dedupKey", 'jitsi-meet-cloud-8x8');

            if (!in_array($dedupKey, $participants)) {
                $participants[] = $dedupKey;
                Yii::$app->cache->set($cacheKey, $participants, 86400); // 1 day retention
                
                // Fix: Check if this is likely the creator (first joiner) and we already have count=1 from Room Created
                $increment = 1;
                if (count($participants) === 1 && $stream->participant_count == 1) {
                     $increment = 0;
                     Yii::info("Jitsi Webhook: First participant join detected. Skipping increment to avoid double-counting creator. Room: $roomName", 'jitsi-meet-cloud-8x8');
                }

                if ($increment > 0) {
                    $stream->updateCounters(['participant_count' => 1]);
                    Yii::info("Jitsi Webhook: Count incremented for room $roomName. New ID: $participantId", 'jitsi-meet-cloud-8x8');
                }
            } else {
                 Yii::info("Jitsi Webhook: Participant $participantId already counted for room $roomName", 'jitsi-meet-cloud-8x8');
            }
        } elseif ($stream) {
             // Fallback if no ID found, but we want to avoid overcounting on refresh.
             // Without ID, we can't dedup. Better to log warning and NOT increment to avoid showing "100 participants" for 1 user refreshing.
             Yii::warning("Jitsi Webhook: PARTICIPANT_JOINED without valid participantId for $roomName", 'jitsi-meet-cloud-8x8');
        }
    }

    private function handleParticipantLeft($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: PARTICIPANT_LEFT for $roomName. Payload: " . json_encode($payload), 'jitsi-meet-cloud-8x8');
        
        $sessionId = $payload['sessionId'] ?? null;
        $stream = null;
        if ($sessionId) {
            $stream = JitsiLiveStream::findOne(['session_id' => $sessionId]);
        }
        if (!$stream) {
            $stream = JitsiLiveStream::findOne(['room_name' => $roomName, 'status' => JitsiLiveStream::STATUS_LIVE]);
        }
        
        if ($stream) {
            // Decrement ACTIVE count
            // Ensure we don't go below 0
            if ($stream->active_count > 0) {
                 $stream->updateCounters(['active_count' => -1]);
                 Yii::info("Jitsi Webhook: Active count decremented for room $roomName", 'jitsi-meet-cloud-8x8');
            }
        }
    }

    private function handleTranscriptionUploaded($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: TRANSCRIPTION_UPLOADED for $roomName", 'jitsi-meet-cloud-8x8');
        $this->updateStreamMetadata($roomName, $payload, 'transcription_url');
    }

    private function handleChatUploaded($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: CHAT_UPLOADED for $roomName", 'jitsi-meet-cloud-8x8');
        $this->updateStreamMetadata($roomName, $payload, 'chat_log_url');
    }

    private function handleDocumentAdded($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: DOCUMENT_ADDED for $roomName", 'jitsi-meet-cloud-8x8');
        // This handles a single file addition, appending to the list
        $sessionId = $payload['sessionId'] ?? null;
        $data = $payload['data'] ?? [];
        $link = $data['preAuthenticatedLink'] ?? $data['url'] ?? $data['fileUrl'] ?? null;
        
        if (!$link) {
             return;
        }

        $stream = $this->findStream($roomName, $sessionId);
        if ($stream) {
            if ($stream->addFileUrl($link) && $stream->save()) {
                Yii::info("Jitsi Webhook: Added file URL to stream {$stream->id}", 'jitsi-meet-cloud-8x8');
            }
        }
    }

    private function handleAggregatedReactions($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: AGGREGATED_REACTIONS for $roomName", 'jitsi-meet-cloud-8x8');
        $sessionId = $payload['sessionId'] ?? null;
        $reactions = $payload['data'] ?? []; // Assuming payload data IS the reactions object or contains it
        
        if (empty($reactions)) {
            return;
        }

        $stream = $this->findStream($roomName, $sessionId);
        if ($stream) {
            $stream->reactions = json_encode($reactions);
            $stream->save();
        }
    }
    
    /**
     * Helper to update simple URL text fields
     */
    private function updateStreamMetadata($roomName, $payload, $attribute)
    {
        $sessionId = $payload['sessionId'] ?? null;
        $data = $payload['data'] ?? [];
        
        // Try multiple common keys for the link
        $link = $data['preAuthenticatedLink'] 
             ?? $data['url'] 
             ?? $data['chatLogUrl'] 
             ?? $data['transcriptionUrl'] 
             ?? $data['fileUrl'] 
             ?? null;

        if (!$link) {
            Yii::warning("Jitsi Webhook: No link found in payload for $attribute. Room: $roomName", 'jitsi-meet-cloud-8x8');
            return;
        }

        $stream = $this->findStream($roomName, $sessionId);
        if ($stream) {
            $stream->$attribute = $link;
            if ($stream->save()) {
                 Yii::info("Jitsi Webhook: Updated $attribute for stream {$stream->id}", 'jitsi-meet-cloud-8x8');
            } else {
                 Yii::error("Jitsi Webhook: Failed to save $attribute for stream {$stream->id}. Errors: " . json_encode($stream->errors), 'jitsi-meet-cloud-8x8');
            }
        } else {
             Yii::warning("Jitsi Webhook: Stream not found for metadata update ($attribute). Room: $roomName", 'jitsi-meet-cloud-8x8');
        }
    }

    private function findStream($roomName, $sessionId)
    {
        $stream = null;
        if ($sessionId) {
            $stream = JitsiLiveStream::findOne(['session_id' => $sessionId]);
        }
        if (!$stream) {
            // Find most recent matching room (Exact match)
             $stream = JitsiLiveStream::find()
                ->where(['room_name' => $roomName])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();
        }
        
        if (!$stream) {
            // Fallback: Case-insensitive search
             $stream = JitsiLiveStream::find()
                ->where(['LOWER(room_name)' => strtolower($roomName)])
                ->orderBy(['created_at' => SORT_DESC])
                ->one();
        }

        return $stream;
    }
}

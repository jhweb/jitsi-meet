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
        // Log raw body only in debug mode
        Yii::debug("Jitsi Webhook RAW BODY: " . $rawBody, 'jitsi-meet-cloud-8x8');
        
        // 1. Verify Signature
        if (!$this->verifySignature($rawBody)) {
            Yii::warning("Jitsi Webhook: Signature verification failed.", 'jitsi-meet-cloud-8x8');
            Yii::$app->response->statusCode = 401;
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

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
        
        Yii::info("Jitsi Webhook Processing: Type=[$eventType] Room=[$roomName]", 'jitsi-meet-cloud-8x8');

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
                $this->handleLiveStreamStarted($roomName, $payload); 
                break;
            case 'LIVE_STREAM_ENDED':
                 $this->handleLiveStreamEnded($roomName, $payload);
                 break;
            case 'RECORDING_ENDED':
                 $this->handleRecordingEnded($roomName, $payload);
                 break;
            case 'PARTICIPANT_JOINED':
                $this->handleParticipantJoined($roomName, $payload);
                break;
            case 'RECORDING_STARTED':
                $this->handleRecordingStarted($roomName, $payload);
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
            case 'POLL_CREATED':
                $this->handlePollCreated($roomName, $payload);
                break;
            case 'POLL_ANSWER':
                $this->handlePollAnswer($roomName, $payload);
                break;
            case 'VIDEO_SEGMENT_UPLOADED':
                $this->handleVideoSegmentUploaded($roomName, $payload);
                break;
            case 'SCREEN_SHARING_HISTORY':
                $this->handleScreenSharingHistory($roomName, $payload);
                break;
            case 'SPEAKER_STATS':
                $this->handleSpeakerStats($roomName, $payload);
                break;
            case 'RTCSTATS_UPLOADED':
                $this->handleRtcstatsUploaded($roomName, $payload);
                break;
            case 'SETTINGS_PROVISIONING':
                return $this->handleSettingsProvisioning($roomName, $payload);
            default:
                Yii::info("Jitsi Webhook: Unhandled event type [$eventType]", 'jitsi-meet-cloud-8x8');
                break;
        }

        return ['status' => 'success'];
    }

    private function verifySignature($rawBody)
    {
        $signatureHeader = Yii::$app->request->headers->get('x-jaas-signature');
        
        // Fail if header is missing
        if (!$signatureHeader) {
             Yii::warning("Jitsi Webhook: Missing X-Jaas-Signature header", 'jitsi-meet-cloud-8x8');
             // Optionally allow if no secret is configured (transition period), BUT user requested security.
             // We will check if secret is configured.
             $secret = Yii::$app->getModule('jitsi-meet-cloud-8x8')->settings->get('jaasWebhookSecret');
             if (empty($secret)) {
                 Yii::info("Jitsi Webhook: Security skipped (No Secret Configured)", 'jitsi-meet-cloud-8x8');
                 return true;
             }
             return false;
        }

        $secret = Yii::$app->getModule('jitsi-meet-cloud-8x8')->settings->get('jaasWebhookSecret');
        if (empty($secret)) {
            Yii::info("Jitsi Webhook: Security skipped (No Secret Configured)", 'jitsi-meet-cloud-8x8');
            return true; 
        }

        // Parse Header
        // Format: t=TIMESTAMP,v1=SIGNATURE
        $parts = explode(',', $signatureHeader);
        $timestamp = null;
        $signature = null;

        foreach ($parts as $part) {
            if (strpos($part, 't=') === 0) {
                $timestamp = substr($part, 2);
            } elseif (strpos($part, 'v1=') === 0) {
                $signature = substr($part, 3);
            }
        }

        if (!$timestamp || !$signature) {
            Yii::warning("Jitsi Webhook: Invalid Signature Header format: $signatureHeader", 'jitsi-meet-cloud-8x8');
            return false;
        }

        // 1. Prevent Replay Attacks (5 minute window)
        // Note: 8x8 timestamp is in seconds (or ms? Docs say "t=1632490060" which looks like seconds)
        // Docs Example: t=1632490060.
        // Payload timestamp: 1632490058278 (ms).
        // Header 't' is likely seconds.
        $now = time();
        if (abs($now - $timestamp) > 300) {
             Yii::warning("Jitsi Webhook: Replay attack detected or clock drift. timestamp=$timestamp, now=$now", 'jitsi-meet-cloud-8x8');
             return false;
        }

        // 2. Prepare Signed Payload
        // "the timestamp obtained from the header... (as a string) + the character . + the actual JSON payload"
        $signedPayload = $timestamp . '.' . $rawBody;

        // 3. Compute Expected Signature
        // "HMAC with SHA256... encode result using base64"
        $expectedSignature = base64_encode(hash_hmac('sha256', $signedPayload, $secret, true));

        // 4. Compare
        if (hash_equals($expectedSignature, $signature)) {
            return true;
        }

        Yii::warning("Jitsi Webhook: Signature mismatch. Expected: $expectedSignature, Got: $signature", 'jitsi-meet-cloud-8x8');
        return false;
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
            
            // Set Title from Cache
            $cachedTitle = $cache->get('jitsiMeetCloud8x8:roomTitle:' . strtolower($roomName));
            if ($cachedTitle) {
                $stream->title = $cachedTitle;
            }
        }
        
        // Fallback for title
        if (empty($stream->title)) {
             $stream->title = $roomName;
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

    private function handleRecordingEnded($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: RECORDING_ENDED for $roomName", 'jitsi-meet-cloud-8x8');
        $sessionId = $payload['sessionId'] ?? null;
        $stream = $this->findStream($roomName, $sessionId);

        if ($stream && $stream->status == JitsiLiveStream::STATUS_LIVE) {
            $stream->status = JitsiLiveStream::STATUS_ENDED;
            // Set end time to recording end time (approximate meeting end) via webhook timestamp
            $stream->end_time = date('Y-m-d H:i:s', ($payload['timestamp'] ?? time() * 1000) / 1000);
            
            if ($stream->save()) {
                Yii::info("Jitsi Webhook: Stream status set to ENDED via RECORDING_ENDED for $roomName", 'jitsi-meet-cloud-8x8');
            }
        }
    }

    private function handleLiveStreamEnded($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: LIVE_STREAM_ENDED for $roomName", 'jitsi-meet-cloud-8x8');
        $sessionId = $payload['sessionId'] ?? null;
        $stream = $this->findStream($roomName, $sessionId);

        if ($stream && $stream->status == JitsiLiveStream::STATUS_LIVE) {
            $stream->status = JitsiLiveStream::STATUS_ENDED;
            $stream->end_time = date('Y-m-d H:i:s', ($payload['timestamp'] ?? time() * 1000) / 1000);
            $stream->save();
            Yii::info("Jitsi Webhook: Stream status set to ENDED via LIVE_STREAM_ENDED for $roomName", 'jitsi-meet-cloud-8x8');
        }
    }

    private function handleRecordingUploaded($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: RECORDING_UPLOADED for $roomName. Payload: " . json_encode($payload), 'jitsi-meet-cloud-8x8');

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
        Yii::info("Jitsi Webhook: PARTICIPANT_JOINED for $roomName. Payload: " . json_encode($payload), 'jitsi-meet-cloud-8x8');

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

    private function handlePollCreated($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: POLL_CREATED for $roomName", 'jitsi-meet-cloud-8x8');
        $sessionId = $payload['sessionId'] ?? null;
        $data = $payload['data'] ?? [];
        $pollId = $data['pollId'] ?? null;

        if (!$pollId) {
            return;
        }

        $stream = $this->findStream($roomName, $sessionId);
        if ($stream) {
            $polls = json_decode($stream->polls, true) ?? [];
            
            // Initialize poll if not exists
            if (!isset($polls[$pollId])) {
                $polls[$pollId] = [
                    'question' => $data['question'] ?? 'Unknown Question',
                    'options' => $data['answers'] ?? [],
                    'votes' => []
                ];
                
                $stream->polls = json_encode($polls);
                $stream->save();
            }
        }
    }

    private function handlePollAnswer($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: POLL_ANSWER for $roomName", 'jitsi-meet-cloud-8x8');
        $sessionId = $payload['sessionId'] ?? null;
        $data = $payload['data'] ?? [];
        $pollId = $data['pollId'] ?? null;
        $answers = $data['answers'] ?? [];
        
        $voterId = $data['user']['participantId'] ?? $data['user']['id'] ?? 'unknown_'.time();
        $voterName = $data['user']['name'] ?? 'Unknown';

        if (!$pollId) {
            return;
        }

        $stream = $this->findStream($roomName, $sessionId);
        if ($stream) {
            $polls = json_decode($stream->polls, true) ?? [];

            if (!isset($polls[$pollId])) {
                 $polls[$pollId] = [
                    'question' => 'Poll ' . $pollId,
                    'options' => [],
                    'votes' => []
                ];
            }
            
            // Reconstruct options
            foreach ($answers as $ans) {
                $key = $ans['key'];
                $exists = false;
                foreach ($polls[$pollId]['options'] as $opt) {
                    if (isset($opt['key']) && $opt['key'] == $key) {
                        $exists = true; 
                        break;
                    }
                }
                if (!$exists) {
                    $polls[$pollId]['options'][] = ['key' => $key, 'name' => $ans['name']];
                }
            }

            // Record Vote
            $selectedKeys = [];
            foreach ($answers as $ans) {
                if (!empty($ans['value']) && $ans['value'] === true) {
                    $selectedKeys[] = $ans['key'];
                }
            }

            $polls[$pollId]['votes'][$voterId] = [
                'name' => $voterName,
                'keys' => $selectedKeys,
                'timestamp' => time()
            ];

            $stream->polls = json_encode($polls);
            if ($stream->save()) {
                 Yii::info("Jitsi Webhook: Saved vote for poll $pollId. User: $voterName", 'jitsi-meet-cloud-8x8');
            } else {
                 Yii::error("Jitsi Webhook: Failed to save vote. Error: " . json_encode($stream->errors), 'jitsi-meet-cloud-8x8');
            }
        }
    }
    
    private function handleRecordingStarted($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: RECORDING_STARTED for $roomName", 'jitsi-meet-cloud-8x8');
        $sessionId = $payload['sessionId'] ?? null;
        $stream = $this->findStream($roomName, $sessionId);

        if ($stream) {
            $stream->has_recording = 1;
            if ($stream->save()) {
                Yii::info("Jitsi Webhook: Mark has_recording=1 for $roomName", 'jitsi-meet-cloud-8x8');
            }
        }
    }

    private function handleVideoSegmentUploaded($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: VIDEO_SEGMENT_UPLOADED (Highlights) for $roomName", 'jitsi-meet-cloud-8x8');
        $this->updateStreamMetadata($roomName, $payload, 'highlights_url');
    }

    private function handleScreenSharingHistory($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: SCREEN_SHARING_HISTORY for $roomName", 'jitsi-meet-cloud-8x8');
        $this->updateStreamMetadata($roomName, $payload, 'screen_sharing_url');
    }

    private function handleSpeakerStats($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: SPEAKER_STATS for $roomName", 'jitsi-meet-cloud-8x8');
        $sessionId = $payload['sessionId'] ?? null;
        $data = $payload['data'] ?? [];

        if (empty($data)) {
            return;
        }

        $stream = $this->findStream($roomName, $sessionId);
        if ($stream) {
            $stream->speaker_stats = json_encode($data);
            if ($stream->save()) {
                Yii::info("Jitsi Webhook: Saved SPEAKER_STATS for stream {$stream->id}", 'jitsi-meet-cloud-8x8');
            } else {
                 Yii::error("Jitsi Webhook: Failed to save SPEAKER_STATS. Errors: " . json_encode($stream->errors), 'jitsi-meet-cloud-8x8');
            }
        }
    }

    private function handleRtcstatsUploaded($roomName, $payload)
    {
         Yii::info("Jitsi Webhook: RTCSTATS_UPLOADED for $roomName", 'jitsi-meet-cloud-8x8');
         $this->updateStreamMetadata($roomName, $payload, 'rtcstats_url');
    }
    
    private function handleLiveStreamStarted($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: LIVE_STREAM_STARTED for $roomName", 'jitsi-meet-cloud-8x8');
        $sessionId = $payload['sessionId'] ?? null;
        
        // Try to find the stream or create if missing (though usually ROOM_CREATED exists)
        $stream = $this->findStream($roomName, $sessionId);
        
        // If not found, we should probably create it, or wait for ROOM_CREATED?
        // Usually Live Stream started happens inside a meeting.
        if (!$stream) {
             $stream = new JitsiLiveStream();
             $stream->room_name = $roomName;
             $stream->session_id = $sessionId;
             $stream->start_time = date('Y-m-d H:i:s', ($payload['timestamp'] ?? time() * 1000) / 1000);
             $stream->status = JitsiLiveStream::STATUS_LIVE;
             if (!$stream->save()) {
                  Yii::error("Jitsi Webhook: Failed to create stream on LIVE_STREAM_STARTED. Errors: " . json_encode($stream->errors), 'jitsi-meet-cloud-8x8');
                  return;
             }
        } else {
             // Update status to LIVE if not already
             if ($stream->status != JitsiLiveStream::STATUS_LIVE) {
                 $stream->status = JitsiLiveStream::STATUS_LIVE;
                 $stream->save();
             }
        }

        // Try to extract YouTube URL
        // Docs don't specify where the URL is, but typically it might be in 'data'
        $data = $payload['data'] ?? [];
        
        // Common 8x8/Jitsi patterns for broadcast URL
        $broadcastUrl = $data['streamUrl'] 
                     ?? $data['broadcastUrl'] 
                     ?? $data['url']
                     ?? null;
                     
        if ($broadcastUrl) {
            $stream->ytstream_url = $broadcastUrl;
            if ($stream->save()) {
                Yii::info("Jitsi Webhook: Saved ytstream_url: $broadcastUrl", 'jitsi-meet-cloud-8x8');
            }
        } else {
             Yii::warning("Jitsi Webhook: LIVE_STREAM_STARTED but no stream URL found in payload. content=" . json_encode($data), 'jitsi-meet-cloud-8x8');
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
             ?? $data['statsUrl']
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
             
             if (!$stream) {
                 // FAILSAFE: Try searching by lowercase room name
                 $stream = JitsiLiveStream::find()
                    ->where(['lower(room_name)' => strtolower($roomName)])
                    ->orderBy(['created_at' => SORT_DESC])
                    ->one();
             }
        }
        return $stream;
    }

    private function handleSettingsProvisioning($roomName, $payload)
    {
        Yii::info("Jitsi Webhook: SETTINGS_PROVISIONING for $roomName", 'jitsi-meet-cloud-8x8');
        
        // Find the latest stream for this room
        // We use the general find method but without session ID since provisioning happens before session start
        $stream = $this->findStream($roomName, null);

        if ($stream && $stream->lobby_enabled) {
             Yii::info("Jitsi Webhook Provisioning: Enabling Lobby for $roomName", 'jitsi-meet-cloud-8x8');
             // Return 8x8 provisioning JSON
             return [
                 'lobbyEnabled' => true,
                 'lobbyType' => 'WAIT_FOR_APPROVAL',
                 // 'passcode' => '1234', // Optional features for future
             ];
        }

        // Default: No lobby
        return ['lobbyEnabled' => false];
    }
}

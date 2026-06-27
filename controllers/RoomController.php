<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\controllers;

use Firebase\JWT\JWT;
use humhub\components\Controller;
use humhubContrib\modules\jitsiMeetCloud8x8\models\JoinRoomForm;
use humhubContrib\modules\jitsiMeetCloud8x8\Module;
use humhubContrib\modules\jitsiMeetCloud8x8\components\JaasJwtService;
use humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream;
use humhubContrib\modules\jitsiMeetCloud8x8\permissions\CanAccess;
use humhubContrib\modules\jitsiMeetCloud8x8\permissions\CanSchedule;
use humhub\modules\content\models\Content;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\calendar\models\reminder\CalendarReminder;
use humhub\modules\content\permissions\ManageContent;
use humhub\modules\space\models\Space;
use humhub\modules\space\models\Membership;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\topic\models\Topic;
use Yii;

/**
 * @property Module $module
 */
class RoomController extends Controller
{
    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [
            ['permissions' => [CanAccess::class], 'actions' => [
                'index', 'create', 'open', 'modal', 'redirect', 'details',
                'view-event', 'toggle-reminder', 'attend', 'invite', 'share'
            ]],
            ['permissions' => [CanSchedule::class], 'actions' => ['schedule', 'delete', 'edit']],
        ];
    }

    /**
     * Modal to create a new stream (Join Room)
     */
    public function actionCreate()
    {
        $model = new JoinRoomForm();
        return $this->renderAjax('create_modal', ['model' => $model]);
    }

    public function actionIndex()
    {
        $model = new JoinRoomForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $rawTitle = $model->room;
            $fixedName = $this->fixRoomName($rawTitle);
            
            // Cache the raw title for Webhook/Stream creation usage
            $cacheKey = 'jitsiMeetCloud8x8:roomTitle:' . strtolower($fixedName);
            Yii::$app->cache->set($cacheKey, $rawTitle, 3600);

            // Cache Lobby Enabled Setting
            if ($model->lobbyEnabled) {
                $lobbyKey = 'jitsiMeetCloud8x8:lobbyEnabled:' . strtolower($fixedName);
                Yii::$app->cache->set($lobbyKey, true, 3600);
            }

            return $this->redirect(['open', 'name' => $fixedName]);
        }

        $entriesPerPage = $this->module->getSettingsForm()->entriesPerPage;
        
        // Get scheduled streams (upcoming)
        $scheduledStreams = [];
        if ($this->module->isSchedulingEnabled()) {
            $scheduledStreams = JitsiLiveStream::find()
                ->where(['status' => JitsiLiveStream::STATUS_SCHEDULED])
                ->orderBy(['scheduled_start' => SORT_ASC])
                ->all();
        }
        
        // Get active/live streams
        $allLiveStreams = JitsiLiveStream::find()
            ->where(['status' => JitsiLiveStream::STATUS_LIVE])
            ->orderBy(['start_time' => SORT_DESC])
            ->all();
            
        $activeStreams = [];
        
        // Filter Premature Live Streams (30-minute rule)
        foreach ($allLiveStreams as $stream) {
            $isPremature = false;
            
            // Check 30-minute window
            if ($stream->scheduled_start) {
                $startTs = strtotime($stream->scheduled_start);
                // If scheduled start is more than 30 minutes in future
                if ($startTs > (time() + 1800)) { 
                    $isPremature = true;
                }
            }
            
            if ($isPremature) {
                // Treat as Scheduled (Hide Live status from public grid)
                $scheduledStreams[] = $stream;
            } else {
                $activeStreams[] = $stream;
            }
        }
        
        // Re-sort scheduled streams by date
        usort($scheduledStreams, function($a, $b) {
            $t1 = $a->scheduled_start ? strtotime($a->scheduled_start) : 0;
            $t2 = $b->scheduled_start ? strtotime($b->scheduled_start) : 0;
            return $t1 - $t2;
        });
        $activeCount = count($activeStreams) + count($scheduledStreams);
        
        // For ended streams, adjust limit on page 1
        $query = JitsiLiveStream::find()->where(['status' => JitsiLiveStream::STATUS_ENDED]);
        $countQuery = clone $query;
        $totalEnded = $countQuery->count();
        
        $pages = new \yii\data\Pagination(['totalCount' => $totalEnded, 'pageSize' => $entriesPerPage]);
        
        $endedLimit = $entriesPerPage;
        if ($pages->page === 0 && $activeCount > 0) {
            $endedLimit = max(0, $entriesPerPage - $activeCount);
        }
        
        // Check if user can schedule
        $canSchedule = $this->module->isSchedulingEnabled() && Yii::$app->user->can(CanSchedule::class);
        
        return $this->render('index', [
            'model' => $model,
            'jitsiDomain' => $this->module->getSettingsForm()->jitsiDomain,
            'scheduledStreams' => $scheduledStreams,
            'activeStreams' => $activeStreams,
            'endedStreams' => $query->offset($pages->offset)
                ->limit($endedLimit)
                ->orderBy(['end_time' => SORT_DESC])
                ->all(),
            'pages' => $pages,
            'canSchedule' => $canSchedule,
        ]);
    }

    /**
     * Schedule a new stream
     */
    public function actionSchedule()
    {
        if (!$this->module->isSchedulingEnabled()) {
            throw new \yii\web\ForbiddenHttpException('Scheduling is not enabled.');
        }

        $model = new JitsiLiveStream();
        $model->status = JitsiLiveStream::STATUS_SCHEDULED;
        $model->creator_id = Yii::$app->user->id;

        if (Yii::$app->request->isAjax && !Yii::$app->request->isPost) {
            // Prepare Calendar Targets (Profile + Spaces)
            $user = Yii::$app->user->getIdentity();
            $calendars = [];
            $disabledOptions = [];
            
            $calendars[$user->contentContainerRecord->guid] = Yii::t('JitsiMeetCloud8x8Module.base', 'Profile: {name}', ['name' => $user->displayName]);
            
            // Fetch Spaces
            $memberships = Membership::findAll(['user_id' => $user->id]);
            foreach ($memberships as $membership) {
                if ($membership->space) {
                    $space = $membership->space;
                    // Filter Private Spaces
                    if ($space->visibility === Space::VISIBILITY_NONE) {
                        continue;
                    }

                    $hasCalendar = $space->isModuleEnabled('calendar');
                    $label = Yii::t('JitsiMeetCloud8x8Module.base', 'Space: {name}', ['name' => $space->displayName]);
                    
                    if (!$hasCalendar) {
                        $label .= ' (' . Yii::t('JitsiMeetCloud8x8Module.base', 'Calendar disabled') . ')';
                        $disabledOptions[$space->contentContainerRecord->guid] = ['disabled' => true];
                    }
                    
                    $calendars[$space->contentContainerRecord->guid] = $label;
                }
            }

            // Fetch Event Types
            $types = [];
            $allTypes = CalendarEntryType::find()->all();
            foreach ($allTypes as $t) {
                $types[$t->id] = $t->name;
            }

            // Render modal form
            return $this->renderAjax('schedule_modal', [
                'model' => $model,
                'calendars' => $calendars,
                'types' => $types,
                'disabledOptions' => $disabledOptions,
                'defaultCalendarGuid' => $user->contentContainerRecord->guid
            ]);
        }

        if ($model->load(Yii::$app->request->post())) {
            // Validate Word Count (500 words)
            // Validate Word Count (500 words)
            // Use stricter whitespace splitting for count
            $rawDesc = $model->description;
            // Decode entities to treat &nbsp; as space
            $decodedDesc = html_entity_decode($rawDesc);
            $cleanDesc = strip_tags($decodedDesc);
            $count = count(preg_split('~[^\p{L}\p{N}\']+~u', $cleanDesc, -1, PREG_SPLIT_NO_EMPTY));
            
            Yii::info("Jitsi Stream Validation: Desc Length: " . strlen($model->description) . ", PHP Word Count: " . $count, 'jitsi-meet-cloud-8x8');
            
            if ($count > 200) {
                $model->addError('description', Yii::t('JitsiMeetCloud8x8Module.base', 'Description cannot exceed 200 words. Current count: {count}', ['count' => $count]));
                
                // Re-fetch data for view
                $user = Yii::$app->user->getIdentity();
                $calendars = [$user->contentContainerRecord->guid => $user->displayName]; // Simplified for error re-render
                $types = [];
                foreach (CalendarEntryType::find()->all() as $t) $types[$t->id] = $t->name;
                
                return $this->renderAjax('schedule_modal', [
                    'model' => $model,
                    'calendars' => $calendars, // Note: This might lose full list if valid, but good enough for error state
                    'types' => $types,
                    'disabledOptions' => [],
                    'defaultCalendarGuid' => $user->contentContainerRecord->guid
                ]);
            }

            // Generate room name from title
            $model->room_name = $this->fixRoomName($model->title ?: 'Stream' . time());
            
            // Ensure scheduled_end is set
            if (empty($model->scheduled_end) && !empty($model->scheduled_start)) {
                $start = new \DateTime($model->scheduled_start);
                $model->scheduled_end = $start->modify('+1 hour')->format('Y-m-d H:i:s');
            }

            if ($model->save()) {
                
                // Phase 2: Deep Calendar Integration - Auto-create Calendar Entry
                if ($this->module->isCalendarEnabled()) {
                    try {
                        $calendarEntry = new \humhub\modules\calendar\models\CalendarEntry();
                        
                        // Resolve Target Content Container
                        $targetGuid = Yii::$app->request->post('target_calendar');
                        $container = null;
                        if ($targetGuid) {
                            $container = ContentContainer::findRecord($targetGuid);
                        }
                        
                        // Check if Space and save space_id
                        if ($container instanceof \humhub\modules\space\models\Space) {
                             $model->space_id = $container->id;
                             $model->save();
                        }

                        if (!$container) {
                            $container = Yii::$app->user->getIdentity(); // Fallback
                        }
                        $calendarEntry->content->setContainer($container);
                        
                        $calendarEntry->title = $model->title;
                        
                        // Do not append Join Link to Description (User Request to prevent leak)
                        // $joinUrl = $model->getUrl();
                        // $joinLink = "### [JOIN WATCH ROOM]($joinUrl)";
                        // $calendarEntry->description = $model->description . "\n\n" . $joinLink;
                        
                        $calendarEntry->description = $model->description;
                        
                        // Set Visibility (Public or Private)
                        $isPublic = Yii::$app->request->post('is_public');
                        $calendarEntry->content->visibility = $isPublic ? Content::VISIBILITY_PUBLIC : Content::VISIBILITY_PRIVATE;

                        // Convert datetime-local format (2026-01-08T10:30) to Y-m-d H:i:s
                        $startDt = new \DateTime($model->scheduled_start);
                        $endDt = new \DateTime($model->scheduled_end);
                        $calendarEntry->start_datetime = $startDt->format('Y-m-d H:i:s');
                        $calendarEntry->end_datetime = $endDt->format('Y-m-d H:i:s');
                        $calendarEntry->all_day = $model->all_day;
                        $calendarEntry->time_zone = $model->timezone;
                        
                        // Set Event Type
                        $typeId = Yii::$app->request->post('type_id');
                        if ($typeId) {
                            $calendarEntry->type_id = $typeId;
                        }
                        
                        // Enable participation and ensure it's published mechanism
                        $calendarEntry->participant_info = 1; 
                        $calendarEntry->participation_mode = 2; // CalendarEntry::PARTICIPATION_MODE_ALL (Hardcoded to prevent undefined constant in older versions)
                        
                        // Force Published State (1)
                        $calendarEntry->content->state = 1; // Content::STATE_PUBLISHED 
                        
                        if ($calendarEntry->save()) {
                            $model->calendar_entry_id = $calendarEntry->id;
                            $model->save();
                            
                            // Attach Topics
                            $topics = Yii::$app->request->post('topics');
                            if (!empty($topics)) {
                                Topic::attach($calendarEntry->content, $topics);
                            }

                            Yii::info("Created Calendar Entry {$calendarEntry->id} for Stream {$model->id}", 'jitsi-meet-cloud-8x8');
                        } else {
                            // Critical Failure: Calendar Entry invalid
                            Yii::error("Failed to create Calendar Entry for Stream {$model->id}: " . json_encode($calendarEntry->errors), 'jitsi-meet-cloud-8x8');
                            
                            // Rollback: Delete the just-created stream so we don't have orphans
                            $model->delete();
                            
                            // Pass errors back to form
                            $model->addError('scheduled_end', Yii::t('JitsiMeetCloud8x8Module.base', 'Calendar Entry creation failed: {errors}', [
                                'errors' => reset($calendarEntry->getFirstErrors())
                            ]));
                            
                            if (Yii::$app->request->isAjax) {
                                return $this->renderAjax('schedule_modal', [
                                    'model' => $model,
                                    'calendars' => $calendars,
                                    'defaultCalendarGuid' => $user->contentContainerRecord->guid
                                ]);
                            }
                            return $this->render('index', ['model' => $model]);
                        }
                    } catch (\Exception $e) {
                         Yii::error("Calendar Integration Error: " . $e->getMessage(), 'jitsi-meet-cloud-8x8');
                         // Rollback
                         $model->delete();
                         $model->addError('title', 'System Error: ' . $e->getMessage());
                         
                         if (Yii::$app->request->isAjax) {
                                return $this->renderAjax('schedule_modal', [
                                    'model' => $model,
                                    'calendars' => $calendars,
                                    'defaultCalendarGuid' => $user->contentContainerRecord->guid
                                ]);
                         }
                    }
                }

                Yii::$app->session->setFlash('success', Yii::t('JitsiMeetCloud8x8Module.base', 'Stream scheduled successfully!'));
                return $this->redirect(['index']);
            }
        }

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('schedule_modal', ['model' => $model]);
        }

        return $this->redirect(['index']);
    }

    public function actionOpen()
    {
        $name = $this->fixRoomName(Yii::$app->request->get('name'));
        $settings = $this->module->getSettingsForm();

        // Enhanced logging for debugging
        Yii::info("RoomController::actionOpen - Room: {$name}", 'jitsi-meet');

        // Check if URL has config.startSilent fragment (for silent join)
        $startSilent = false;
        $requestUri = Yii::$app->request->getUrl();
        if (strpos($requestUri, '#config.startSilent=true') !== false || 
            Yii::$app->request->get('startSilent') === 'true') {
            $startSilent = true;
            Yii::info("RoomController::actionOpen - Silent join requested", 'jitsi-meet');
        }

        // Security: Check 30-minute rule for scheduled streams
        $stream = JitsiLiveStream::find()->where(['room_name' => $name])->one();
        if ($stream && $stream->scheduled_start && !Yii::$app->user->isGuest) {
            $startTs = strtotime($stream->scheduled_start);
            // If more than 30 mins before start
            if ($startTs > (time() + 1800)) {
                $user = Yii::$app->user->getIdentity();
                // Only creator or system admin can join early (for testing)
                if ($stream->creator_id != $user->id && !$user->isSystemAdmin()) {
                     throw new \yii\web\ForbiddenHttpException(Yii::t('JitsiMeetCloud8x8Module.base', 'This event has not started yet. You can join 30 minutes before the start time.'));
                }
            }
        }

        // Default modal route and params
        $jitsiRoomUrl = ['/jitsi-meet-cloud-8x8/room/modal', 'name' => $name];
        if ($startSilent) {
            $jitsiRoomUrl['startSilent'] = 'true';
        }

        // Determine mode
        $mode = $settings->mode ?: 'self_hosted';
        Yii::info("RoomController::actionOpen - Mode: {$mode}", 'jitsi-meet');

        if ($mode === 'jaas') {
            Yii::info('RoomController::actionOpen - JaaS mode selected', 'jitsi-meet');
            
            if (Yii::$app->user->isGuest) {
                Yii::info('RoomController::actionOpen - User is guest, requiring login', 'jitsi-meet');
                Yii::$app->user->loginRequired();
            }
            
            $user = Yii::$app->user->getIdentity();
            
            // Fix: Ensure creator is set in cache regardless of admin status
            $this->ensureRoomCreator($name, $user);
            
            $isModerator = $this->isModeratorForCurrentContext($name);
            
            Yii::info("RoomController::actionOpen - User: {$user->displayName} (ID: {$user->id}), Moderator: " . ($isModerator ? 'true' : 'false'), 'jitsi-meet');
            
            $jwt = JaasJwtService::createToken($user, $name, $isModerator);
            if (!empty($jwt)) {
                $jitsiRoomUrl['jwt'] = $jwt;
                Yii::info('RoomController::actionOpen - JWT generated and added to URL', 'jitsi-meet');
            } else {
                Yii::error('RoomController::actionOpen - JWT generation failed', 'jitsi-meet');
            }
        } else {
            Yii::info('RoomController::actionOpen - Self-hosted mode selected', 'jitsi-meet');
            // Legacy HS256 path
            if ($this->module->getSettingsForm()->enableJwt) {
                if (Yii::$app->user->isGuest) {
                    Yii::$app->user->loginRequired();
                }
                $jitsiRoomUrl['jwt'] = $this->createJWT($name);
                Yii::info('RoomController::actionOpen - Legacy JWT generated', 'jitsi-meet');
            }
        }

        $domain = $mode === 'jaas' ? $settings->jaasDomain : $settings->jitsiDomain;
        Yii::info("RoomController::actionOpen - Using domain: {$domain}", 'jitsi-meet');

        $this->layout = "@humhub/modules/user/views/layouts/main";
        return $this->render('open', [
            'jitsiDomain' => $domain,
            'jitsiRoomUrl' => $jitsiRoomUrl,
            'startSilent' => $startSilent,
        ]);
    }

    private function createJWT($roomName)
    {
        // security measure: if the current user is not authenticated, don‘t create a token
        if (Yii::$app->user->isGuest) {
            return "";
        }
        $user = Yii::$app->user->getIdentity();
        // security measure: if we can‘t get the user‘s identity, don‘t create a token
        if (is_null($user)) {
            return "";
        }
        $userEmail = $user->email;
        $userName = $user->displayName;
        $issuedAt = time();
        $notBefore = $issuedAt + 10; //Adding 10 seconds
        $expire = $notBefore + 60; // Adding 60 seconds
        $jitsi = $this->module->getSettingsForm()->jitsiDomain;
        $appID = $this->module->getSettingsForm()->jitsiAppID;
        $prefix = $this->module->getSettingsForm()->roomPrefix;
        $token = [
            'iss' => $appID,
            'aud' => $jitsi,
            'sub' => $jitsi,
            'exp' => $expire,
            'room' => $prefix . $roomName,
            'context' => [
                'user' => [
                    'name' => $userName,
                    'email' => $userEmail,
                    'avatar' => (string)($user->getProfileImage() ? \yii\helpers\Url::to($user->getProfileImage()->getUrl(), true) : ''),
                ],
            ],
        ];

        return JWT::encode($token, (string) $this->module->getSettingsForm()->jitsiAppSecret, 'HS256');
    }

    public function actionModal()
    {
        $name = $this->fixRoomName(Yii::$app->request->get('name'));
        $jwt = Yii::$app->request->get('jwt');
        $startSilent = Yii::$app->request->get('startSilent') === 'true';

        Yii::info("RoomController::actionModal - Room: {$name}, JWT present: " . (!empty($jwt) ? 'yes' : 'no') . ", StartSilent: " . ($startSilent ? 'yes' : 'no'), 'jitsi-meet');

        if (!Yii::$app->request->isAjax) {
            Yii::info('RoomController::actionModal - Not AJAX request, redirecting', 'jitsi-meet');
            $redirectUrl = ['open', 'name' => $name];
            if ($startSilent) {
                $redirectUrl['startSilent'] = 'true';
            }
            return $this->redirect($redirectUrl);
        }

        return $this->renderAjax('modal', [
            'jwt' => $jwt,
            'name' => $name,
            'startSilent' => $startSilent,
        ]);

    }

    /**
     * Redirect action - handles old vpaas-magic-cookie URL format and redirects to new /conference/{name} format
     * This ensures backward compatibility with links generated by recording bot
     */
    public function actionRedirect()
    {
        $name = $this->fixRoomName(Yii::$app->request->get('name'));
        $appId = Yii::$app->request->get('appId');
        
        Yii::info("RoomController::actionRedirect - Redirecting from old format (appId: {$appId}, room: {$name}) to new format", 'jitsi-meet');
        
        // Redirect to the new /conference/{name} format
        return $this->redirect("/conference/{$name}", 301); // 301 permanent redirect
    }

    /**
     * Invite service action - custom invite service for Jitsi Meet
     * This endpoint handles invitation requests and can also return room URL information
     * Note: inviteServiceUrl is primarily for sending invitations, but we can use it to override URL format
     * Handles both GET and POST requests
     */
    /**
     * Renders stream details modal
     * @param int $id Stream ID
     */
    public function actionDetails($id)
    {
        $stream = JitsiLiveStream::findOne($id);
        if (!$stream) {
            throw new \yii\web\NotFoundHttpException();
        }

        $chatMessages = [];
        if (!empty($stream->chat_log_url)) {
            // Fetch chat log with a 5-second timeout
            $context = stream_context_create(['http' => ['timeout' => 5]]); 
            $content = @file_get_contents($stream->chat_log_url, false, $context);
            
            if ($content !== false) {
                // Check for GZIP magic bytes (1f 8b)
                if (strlen($content) >= 2 && ord($content[0]) == 0x1f && ord($content[1]) == 0x8b) {
                    $decoded = @gzdecode($content);
                    if ($decoded !== false) {
                        $content = $decoded;
                    }
                }
                
                $data = json_decode($content, true);
                if (is_array($data)) {
                    // Try to find the messages array
                    $candidates = [$data]; // start with root
                    if (isset($data['messages'])) $candidates[] = $data['messages'];
                    if (isset($data['data'])) $candidates[] = $data['data'];
                    if (isset($data['payload'])) $candidates[] = $data['payload'];
                    
                    foreach ($candidates as $cand) {
                        if (is_array($cand) && count($cand) > 0) {
                            // Relaxed Heuristic: accept array if it looks list-like (indexed keys) or first element is array
                            // Just check if it's a list of arrays
                            $first = reset($cand);
                            if (is_array($first)) {
                                $chatMessages = $cand;
                                break;
                            }
                        }
                    }
                }
            }
        }

        $screenSharingContent = [];
        if (!empty($stream->screen_sharing_url)) {
            $context = stream_context_create(['http' => ['timeout' => 3]]);
            $content = @file_get_contents($stream->screen_sharing_url, false, $context);
            if ($content !== false) {
                $screenSharingContent = json_decode($content, true);
            }
        }

        return $this->renderAjax('modal_details', [
            'stream' => $stream,
            'chatMessages' => $chatMessages,
            'screenSharingContent' => $screenSharingContent
        ]);
    }

    /**
     * Custom modal view for scheduled event details
     * Bypasses Calendar module container routing issues
     * @param int $id Stream ID
     */
    public function actionViewEvent($id)
    {
        $stream = JitsiLiveStream::findOne($id);
        if (!$stream || !$stream->calendarEntry) {
            throw new \yii\web\NotFoundHttpException('Event not found.');
        }

        $calendarEntry = $stream->calendarEntry;
        
        // Get current user's participation status
        $isAttending = false;
        if (!Yii::$app->user->isGuest) {
            $isAttending = (new \yii\db\Query())
                ->from('calendar_entry_participant')
                ->where(['calendar_entry_id' => $calendarEntry->id, 'user_id' => Yii::$app->user->id])
                ->andWhere(['participation_state' => 2]) // 2 = Accepted
                ->exists();
        }

        // Get all attendees (participation_state = 2)
        $attendeeIds = (new \yii\db\Query())
            ->select(['user_id'])
            ->from('calendar_entry_participant')
            ->where(['calendar_entry_id' => $calendarEntry->id, 'participation_state' => 2])
            ->column();
        
        $attendees = [];
        if (!empty($attendeeIds)) {
            $attendees = \humhub\modules\user\models\User::find()
                ->where(['id' => $attendeeIds])
                ->limit(20) // Limit to 2 icons
                ->all();
        }

        // Check if reminder is set
        $hasReminder = false;
        if (!Yii::$app->user->isGuest) {
            $userContainer = Yii::$app->user->getIdentity()->contentContainerRecord;
            if ($userContainer && $calendarEntry->content) {
                $hasReminder = CalendarReminder::find()
                    ->where([
                        'content_id' => $calendarEntry->content->id,
                        'contentcontainer_id' => $userContainer->id
                    ])
                    ->exists();
            }
        }

        return $this->renderAjax('modal_event', [
            'stream' => $stream,
            'calendarEntry' => $calendarEntry,
            'isAttending' => $isAttending,
            'attendees' => $attendees,
            'attendeeCount' => count($attendeeIds),
            'hasReminder' => $hasReminder,
        ]);
    }

    /**
     * Toggle Custom Reminder (33 min & 1 Day)
     */
    public function actionToggleReminder($id)
    {
        if (Yii::$app->user->isGuest) {
            return Yii::$app->user->loginRequired();
        }

        $stream = JitsiLiveStream::findOne($id);
        if (!$stream || !$stream->calendarEntry) {
            throw new \yii\web\NotFoundHttpException();
        }
        
        $calendarEntry = $stream->calendarEntry;
        $userContainer = Yii::$app->user->getIdentity()->contentContainerRecord;
        
        if (!$userContainer || !$calendarEntry->content) {
             return $this->asJson(['success' => false, 'message' => 'Invalid content or user']);
        }

        // Check existing
        $reminders = CalendarReminder::find()
             ->where([
                'content_id' => $calendarEntry->content->id,
                'contentcontainer_id' => $userContainer->id
            ])
            ->all();
            
        if (count($reminders) > 0) {
            // Remove
            foreach ($reminders as $r) $r->delete();
            return $this->asJson(['success' => true, 'reminder' => false]);
        } else {
            // Add Custom Reminders
            // 1. 33 Minutes
            $r1 = new CalendarReminder();
            $r1->content_id = $calendarEntry->content->id;
            $r1->contentcontainer_id = $userContainer->id;
            $r1->unit = CalendarReminder::UNIT_MINUTE;
            $r1->value = 33;
            $r1->active = 1;
            $r1->save();
            
            // 2. 1 Day
            $r2 = new CalendarReminder();
            $r2->content_id = $calendarEntry->content->id;
            $r2->contentcontainer_id = $userContainer->id;
            $r2->unit = CalendarReminder::UNIT_DAY;
            $r2->value = 1;
            $r2->active = 1;
            $r2->save();
            
            return $this->asJson(['success' => true, 'reminder' => true]);
        }
    }

    /**
     * Handle RSVP action for scheduled events
     * Bypasses Calendar module container routing issues
     * @param int $id Stream ID
     * @param int $type Response type (2=Attend, 3=Maybe, 4=Decline)
     */
    public function actionAttend($id, $type = null)
    {
        if (Yii::$app->user->isGuest) {
            throw new \yii\web\ForbiddenHttpException('You must be logged in.');
        }

        if ($type === null) {
            $type = CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED;
        }

        $stream = JitsiLiveStream::findOne($id);
        if (!$stream || !$stream->calendarEntry) {
            throw new \yii\web\NotFoundHttpException('Event not found.');
        }

        $calendarEntry = $stream->calendarEntry;
        $userId = Yii::$app->user->id;

        // Check if already a participant
        $existing = (new \yii\db\Query())
            ->from('calendar_entry_participant')
            ->where(['calendar_entry_id' => $calendarEntry->id, 'user_id' => $userId])
            ->one();

        if ($existing) {
            // Update existing participation
            Yii::$app->db->createCommand()->update('calendar_entry_participant', [
                'participation_state' => (int)$type,
            ], ['calendar_entry_id' => $calendarEntry->id, 'user_id' => $userId])->execute();
        } else {
            // Insert new participation
            Yii::$app->db->createCommand()->insert('calendar_entry_participant', [
                'calendar_entry_id' => $calendarEntry->id,
                'user_id' => $userId,
                'participation_state' => (int)$type,
            ])->execute();
        }

        if (Yii::$app->request->isAjax) {
            return $this->asJson(['success' => true, 'message' => 'RSVP updated']);
        }

        Yii::$app->session->setFlash('success', Yii::t('JitsiMeetCloud8x8Module.base', 'Your response has been recorded.'));
        return $this->redirect(['index']);
    }

    public function actionInvite()
    {
        $settings = $this->module->getSettingsForm();
        $mode = $settings->mode ?: 'self_hosted';

        // Security check: JaaS mode requires login for guests
        if ($mode === 'jaas') {
            if (Yii::$app->user->isGuest) {
                Yii::info('RoomController::actionInvite - User is guest in JaaS mode, requiring login', 'jitsi-meet');
                Yii::$app->user->loginRequired();
            }
        } else {
            // Self-hosted mode with JWT also requires login
            if ($settings->enableJwt && Yii::$app->user->isGuest) {
                Yii::info('RoomController::actionInvite - User is guest in secured self-hosted mode, requiring login', 'jitsi-meet');
                Yii::$app->user->loginRequired();
            }
        }

        $request = Yii::$app->request;
        $method = $request->method;
        
        // For POST requests, this is an invitation send request
        // For GET requests, this might be a request for room URL
        if ($method === 'POST') {
            // Handle invitation send request
            $invitees = $request->post('invitees', []);
            Yii::info("RoomController::actionInvite - POST request with invitees: " . json_encode($invitees), 'jitsi-meet');
            
            // Get room name from referer or other source
            // The room name might be in the full URL format
            $referer = $request->getReferrer();
            $roomName = null;
            
            if ($referer) {
                // Extract room name from referer URL
                if (preg_match('/\/conference\/([^\/\?#]+)/', $referer, $matches)) {
                    $roomName = $matches[1];
                } elseif (preg_match('/vpaas-magic-cookie-[a-f0-9]{32}\/([^\/\?#]+)/', $referer, $matches)) {
                    $roomName = $matches[1];
                }
            }
            
            // Also check if room name is passed directly
            $roomName = $roomName ?: $request->post('room') ?: $request->get('room');
            
            if ($roomName) {
                // Extract just the room name if it contains app ID
                if (preg_match('/vpaas-magic-cookie-[a-f0-9]{32}\/(.+)$/', $roomName, $matches)) {
                    $roomName = $matches[1];
                } elseif (preg_match('/^(.+)\/(.+)$/', $roomName, $matches)) {
                    $roomName = $matches[2];
                }
                $roomName = $this->fixRoomName($roomName);
            }
            
            // Return success response
            return $this->asJson([
                'success' => true,
                'roomName' => $roomName,
                'inviteURL' => $roomName ? $this->module->getRoomUrl($roomName, true) : null,
            ]);
        } else {
            // GET request - return room URL information
            $roomName = $request->get('room') ?: $request->get('name');
            
            // If room name contains app ID prefix, extract just the room name
            if ($roomName && preg_match('/vpaas-magic-cookie-[a-f0-9]{32}\/(.+)$/', $roomName, $matches)) {
                $roomName = $matches[1];
            } elseif ($roomName && preg_match('/^(.+)\/(.+)$/', $roomName, $matches)) {
                $roomName = $matches[2];
            }
            
            if ($roomName) {
                $roomName = $this->fixRoomName($roomName);
                $conferenceUrl = $this->module->getRoomUrl($roomName, true);
                $conferenceUrlSilent = $this->module->getRoomUrlSilent($roomName, true);
                
                return $this->asJson([
                    'inviteURL' => $conferenceUrl,
                    'inviteURLSilent' => $conferenceUrlSilent,
                    'roomName' => $roomName,
                ]);
            }
        }
        
        Yii::info("RoomController::actionInvite - Request processed, Method: {$method}", 'jitsi-meet');
        return $this->asJson(['success' => true]);
    }

    /**
     * Share action - returns room URL information for sharing/invitations
     * This ensures correct URL format is used instead of vpaas-magic-cookie format
     */
    public function actionShare()
    {
        $name = $this->fixRoomName(Yii::$app->request->get('name'));
        
        if (empty($name)) {
            return $this->asJson([
                'error' => 'Room name is required'
            ]);
        }

        $roomUrl = $this->module->getRoomUrl($name, true);
        $roomUrlSilent = $this->module->getRoomUrlSilent($name, true);
        $dialInNumbersUrl = $this->module->getDialInNumbersUrl($name, true);

        return $this->asJson([
            'roomName' => $name,
            'roomUrl' => $roomUrl,
            'roomUrlSilent' => $roomUrlSilent,
            'dialInNumbersUrl' => $dialInNumbersUrl,
        ]);
    }

    private function ensureRoomCreator($roomName, $user)
    {
        if (empty($roomName) || !$user) {
            return;
        }

        $cache = Yii::$app->cache;
        if ($cache) {
            // FIX: Normalize room name to lowercase for cache key to match Webhooks
            $cacheKey = 'jitsiMeetCloud8x8:roomCreator:' . strtolower($roomName);
            $creatorId = $cache->get($cacheKey);

            if ($creatorId === false) {
                // No creator yet -> set current user
                // TTL 1 hour (matches isModeratorForCurrentContext logic)
                $cache->set($cacheKey, $user->id, 3600);
                Yii::info("ensureRoomCreator: Set user {$user->id} as creator for room '{$roomName}' (key: $cacheKey)", 'jitsi-meet');
            }
        }
    }
    
    private function fixRoomName($name)
    {

        if (empty($name)) {
            $name = Yii::$app->user->getIdentity()->profile->firstname;
            $name .= " Square";
        }
        $name = ucwords((string) $name);
        $name = preg_replace("/[^A-Za-z0-9]/", '', $name);

        return $name;
    }

    private function isModeratorForCurrentContext(?string $roomName = null): bool
    {
        /**
         * IMPORTANT:
         * Join rules vs moderator rules:
         *
         * - Any authenticated HumHub member can **join or start** a video chat.
         *   (Join is not restricted by this method, only the moderator flag.)
         * - Moderator status requires BOTH:
         *      • Having the "Can be moderator" permission (controlled per group in HumHub)
         *      • AND being either:
         *          - System admin (always allowed)
         *          - OR the "chat starter" (first user who opened the room name)
         *
         * - Guests are never moderators.
         * - Recording / livestreaming capabilities are STILL controlled separately
         *   via the dedicated permissions in `JaasJwtService::$features`.
         *
         * This keeps "join" completely open to all members while giving you
         * explicit control over who can be moderators via the permission system.
         */

        Yii::info("isModeratorForCurrentContext: evaluating moderator status for room='{$roomName}'", 'jitsi-meet');

        // Guest users are never moderators
        if (Yii::$app->user->isGuest) {
            Yii::info('isModeratorForCurrentContext: guest user => moderator=false', 'jitsi-meet');
            return false;
        }

        $user = Yii::$app->user->getIdentity();

        // Check if user has the "Can be moderator" permission
        $hasModeratorPermission = Yii::$app->user->can(\humhubContrib\modules\jitsiMeetCloud8x8\permissions\CanBeModerator::class);
        Yii::info("isModeratorForCurrentContext: user {$user->id} has CanBeModerator permission: " . ($hasModeratorPermission ? 'yes' : 'no'), 'jitsi-meet');

        // Global admins are always moderators (bypass permission check for admins)
        if ($user->isSystemAdmin()) {
            Yii::info("isModeratorForCurrentContext: user {$user->id} is system admin => moderator=true", 'jitsi-meet');
            return true;
        }

        // If user doesn't have moderator permission, they can never be moderator
        // (even if they're the chat starter)
        if (!$hasModeratorPermission) {
            Yii::info("isModeratorForCurrentContext: user {$user->id} lacks CanBeModerator permission => moderator=false", 'jitsi-meet');
            return false;
        }

        // User has permission, now check if they're the chat starter
        if (!empty($roomName)) {
            $cache = Yii::$app->cache ?? null;
            if ($cache !== null) {
                $cacheKey = 'jitsiMeetCloud8x8:roomCreator:' . $roomName;
                $creatorId = $cache->get($cacheKey);

                if ($creatorId === false) {
                    // No creator yet -> current user becomes the chat starter
                    // TTL 1 hour; adjust if you want longer/shorter "ownership"
                    $cache->set($cacheKey, $user->id, 3600);
                    Yii::info("isModeratorForCurrentContext: user {$user->id} set as chat starter for room '{$roomName}' (cacheKey={$cacheKey}) => moderator=true", 'jitsi-meet');
                    return true;
                }

                if ((int)$creatorId === (int)$user->id) {
                    Yii::info("isModeratorForCurrentContext: user {$user->id} is existing chat starter for room '{$roomName}' (cacheKey={$cacheKey}) => moderator=true", 'jitsi-meet');
                    return true;
                }

                Yii::info("isModeratorForCurrentContext: user {$user->id} is NOT chat starter for room '{$roomName}' (creatorId={$creatorId}, cacheKey={$cacheKey}) => moderator=false", 'jitsi-meet');
            } else {
                Yii::warning("isModeratorForCurrentContext: cache component not available for room '{$roomName}', cannot determine chat starter => moderator=false", 'jitsi-meet');
            }
        }

        // User has permission but is not chat starter and not admin
        Yii::info("isModeratorForCurrentContext: user {$user->id} has permission but is not chat starter => moderator=false", 'jitsi-meet');
        return false;
    }
    /**
     * Delete a stream
     * @param int $id
     */
    public function actionDelete($id)
    {
        $stream = JitsiLiveStream::findOne($id);
        if (!$stream) {
            throw new \yii\web\NotFoundHttpException();
        }

        // Access Check: Creator or Space Admin or System Admin
        $canDelete = false;

        // 1. Creator
        if ($stream->creator_id == Yii::$app->user->id) {
            $canDelete = true;
        } 
        // 2. System Admin
        elseif (Yii::$app->user->isAdmin()) {
            $canDelete = true;
        } 
        // 3. Space Admin
        elseif ($stream->space && $stream->space->isAdmin()) {
            $canDelete = true;
        }
        // 4. Fallback: Check if user is admin of the container if it's a profile/space linked via calendar entry
        elseif ($stream->calendarEntry) {
             $container = $stream->calendarEntry->content->container;
             if ($container && $container->can(ManageContent::class)) {
                 $canDelete = true;
             }
        }

        if (!$canDelete) {
            throw new \yii\web\ForbiddenHttpException('You are not allowed to delete this stream.');
        }

        // Delete Calendar Entry if exists (this will also delete the stream via Foreign Key if set, but we do it manually to be safe)
        if ($stream->calendarEntry) {
            $stream->calendarEntry->delete();
        }

        $stream->delete();
        
        Yii::$app->session->setFlash('success', Yii::t('JitsiMeetCloud8x8Module.base', 'Stream deleted successfully.'));
        return $this->redirect(['index']);
    }

    /**
     * Edit a scheduled stream
     * @param int $id
     */
    public function actionEdit($id)
    {
        $model = JitsiLiveStream::findOne($id);
        if (!$model) {
            throw new \yii\web\NotFoundHttpException();
        }

        // Access Check: Creator or Space Admin or System Admin
        $canEdit = false;
        if ($model->creator_id == Yii::$app->user->id) {
            $canEdit = true;
        } elseif (Yii::$app->user->isAdmin()) {
            $canEdit = true;
        } elseif ($model->space && $model->space->isAdmin()) {
            $canEdit = true;
        }

        if (!$canEdit) {
            throw new \yii\web\ForbiddenHttpException('You are not allowed to edit this stream.');
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
             if (strtotime($model->scheduled_end) <= strtotime($model->scheduled_start)) {
                 $model->addError('scheduled_end', Yii::t('JitsiMeetCloud8x8Module.base', 'End time must be after start time'));
            } else {
                 $model->start_time = (new \DateTime($model->scheduled_start))->format('Y-m-d H:i:s');
                 $model->end_time = (new \DateTime($model->scheduled_end))->format('Y-m-d H:i:s');

                 if ($model->save()) {
                    // Update Calendar Entry
                    if ($model->calendarEntry) {
                        $calendarEntry = $model->calendarEntry;
                        $calendarEntry->title = $model->title;
                        $calendarEntry->description = $model->description . "\n\n### [JOIN WATCH ROOM](" . $model->getUrl() . ")";
                        
                        $startDt = new \DateTime($model->scheduled_start);
                        $endDt = new \DateTime($model->scheduled_end);
                        $calendarEntry->start_datetime = $startDt->format('Y-m-d H:i:s');
                        $calendarEntry->end_datetime = $endDt->format('Y-m-d H:i:s');
                        $calendarEntry->time_zone = $model->timezone;
                        
                        $calendarEntry->save();
                    }
                    
                    Yii::$app->session->setFlash('success', Yii::t('JitsiMeetCloud8x8Module.base', 'Stream updated.'));
                    return $this->redirect(['index']);
                 }
            }
        }
        
        // Prepare Calendar Targets (Reuse logic)
        $user = Yii::$app->user->getIdentity();
        $calendars = [];
        $disabledOptions = [];
        
        $calendars[$user->contentContainerRecord->guid] = Yii::t('JitsiMeetCloud8x8Module.base', 'Profile: {name}', ['name' => $user->displayName]);
        
        $memberships = Membership::findAll(['user_id' => $user->id]);
        foreach ($memberships as $membership) {
            if ($membership->space) {
                $space = $membership->space;
                
                // Filter Private Spaces
                if ($space->visibility === Space::VISIBILITY_NONE) {
                    continue;
                }
                
                $hasCalendar = $space->isModuleEnabled('calendar');
                $label = Yii::t('JitsiMeetCloud8x8Module.base', 'Space: {name}', ['name' => $space->displayName]);
                if (!$hasCalendar) {
                    $label .= ' (' . Yii::t('JitsiMeetCloud8x8Module.base', 'Calendar disabled') . ')';
                    $disabledOptions[$space->contentContainerRecord->guid] = ['disabled' => true];
                }
                $calendars[$space->contentContainerRecord->guid] = $label;
            }
        }
        
        $defaultCalendarGuid = $user->contentContainerRecord->guid;
        if ($model->calendarEntry) {
            $defaultCalendarGuid = $model->calendarEntry->content->container->guid;
        }

        return $this->renderAjax('schedule_modal', [
            'model' => $model,
            'calendars' => $calendars,
            'disabledOptions' => $disabledOptions,
            'defaultCalendarGuid' => $defaultCalendarGuid
        ]);
    }
}

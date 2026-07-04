<?php

use humhub\widgets\ModalDialog;
use humhub\widgets\ModalButton;
use yii\helpers\Html;
use humhub\modules\user\widgets\Image;

/* @var $stream \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream */
/* @var $chatLogContent string|null */
/* @var $screenSharingContent array */

// Calculate availability
$hasRecording = (!empty($stream->recording_url) || $stream->has_recording);
$hasHighlights = !empty($stream->highlights_url);
$hasChat = !empty($stream->chat_log_url);
$polls = $stream->getPolls();
$hasSessionData = (($stream->participant_count > 1) || !empty($stream->reactions) || !empty($polls) || $hasChat);
$hasTranscript = !empty($stream->transcription_url);
$hasExtraFiles = !empty($stream->file_urls);
$feedback = $stream->getFeedback();
$hasFeedback = !empty($feedback);

// Status checks (processing/expired)
$isProcessing = false;
$isExpired = false;
$secondsRemaining = 0;

if ($stream->status == \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream::STATUS_ENDED) {
    if (!empty($stream->end_time)) {
        $secondsSinceEnd = time() - strtotime($stream->end_time);
        
        // Processing Check (< 1 hour and no data)
        if (!$hasRecording && !$hasHighlights && !$hasChat && $secondsSinceEnd < 3600) {
            $isProcessing = true;
        }
        
        // Expiration Check (> 24 hours)
        if ($secondsSinceEnd > (24 * 60 * 60)) {
            $isExpired = true;
        } else {
            $secondsRemaining = (24 * 60 * 60) - $secondsSinceEnd;
        }
    }
}
?>

<?php ModalDialog::begin(['size' => 'large', 'class' => 'jitsi-modal-overrides jitsi-stream-details']) ?>
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">
            <?= Html::encode($stream->title) ?>
            <br>
            <small style="font-size: 13px; color: var(--jitsi-text-secondary);">
                <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Streamed on {date}', ['date' => Yii::$app->formatter->asDate($stream->start_time, 'long')]) ?>
            </small>
        </h4>
        
        <!-- 24h Expiration Countdown -->
        <?php if (!$isExpired && !$isProcessing && $secondsRemaining > 0): ?>
        <div style="margin-top: 10px; font-size: 13px; color: #e67e22; background: rgba(230, 126, 34, 0.1); padding: 5px 10px; border-radius: 4px; display: inline-block;">
            <i class="fa fa-clock-o"></i> 
            <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Links expire in {time}', [
                'time' => Yii::$app->formatter->asDuration($secondsRemaining)
            ]) ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="modal-body">
        
        <?php if ($isProcessing): ?>
            <div class="alert alert-info">
                <i class="fa fa-spinner fa-spin"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Stream data is currently processing. Please check back later.') ?>
            </div>
        <?php else: ?>

            <div class="tab-menu">
                <ul class="nav nav-tabs" role="tablist">
                    <?php 
                        // Determine default active tab
                        $activeTab = 'downloads'; // Default fallback
                        if (($hasRecording || $hasHighlights) && !$isExpired) {
                            $activeTab = 'watch';
                        } elseif ($hasSessionData && !$hasRecording && !$hasHighlights) {
                           // If no recordings but session data, maybe prefer session? 
                           // But user asked for Downloads first if blank.
                           // Let's stick to Downloads as secondary default.
                        }
                        
                        // Override: if Downloads is default but no content for it (unlikely as it has generic files), 
                        // actually Downloads is always safe fallback.
                    ?>

                    <?php if (($hasRecording || $hasHighlights) && !$isExpired): ?>
                    <li role="presentation" class="<?= ($activeTab == 'watch') ? 'active' : '' ?>">
                        <a href="#tab-watch" aria-controls="tab-watch" role="tab" data-toggle="tab">
                            <i class="fa fa-play-circle"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Watch Replay') ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li role="presentation" class="<?= ($activeTab == 'downloads') ? 'active' : '' ?>">
                        <a href="#tab-downloads" aria-controls="tab-downloads" role="tab" data-toggle="tab">
                            <i class="fa fa-download"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Downloads') ?>
                        </a>
                    </li>

                    <?php if ($hasSessionData): ?>
                    <li role="presentation">
                        <a href="#tab-session" aria-controls="tab-session" role="tab" data-toggle="tab">
                            <i class="fa fa-bar-chart"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Session Data') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($hasChat): ?>
                    <li role="presentation">
                        <a href="#tab-chat" aria-controls="tab-chat" role="tab" data-toggle="tab">
                            <i class="fa fa-comments"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Chat Log') ?>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($hasFeedback): ?>
                    <li role="presentation">
                        <a href="#tab-feedback" aria-controls="tab-feedback" role="tab" data-toggle="tab">
                            <i class="fa fa-star"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Feedback') ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="tab-content" style="padding-top: 20px;">
                
                <!-- WATCH TAB -->
                <?php if (($hasRecording || $hasHighlights) && !$isExpired): ?>
                <div role="tabpanel" class="tab-pane <?= ($activeTab == 'watch') ? 'active' : '' ?>" id="tab-watch">
                    
                    <?php if ($hasHighlights): ?>
                        <div class="video-container" style="margin-bottom: 30px;">
                            <h5><i class="fa fa-film"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Highlights') ?></h5>
                            <video width="100%" controls style="background: #000; border-radius: 8px;" preload="metadata">
                                <source src="<?= Html::encode($stream->highlights_url) ?>" type="video/mp4">
                                <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Your browser does not support the video tag.') ?>
                            </video>
                            <div class="text-right" style="margin-top: 5px;">
                                <a href="<?= Html::encode($stream->highlights_url) ?>" target="_blank" class="btn btn-default btn-sm">
                                    <i class="fa fa-external-link"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Open in new tab') ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasRecording): ?>
                        <div class="video-container" style="margin-bottom: 20px;">
                            <?php if ($hasHighlights): ?><h5><i class="fa fa-video-camera"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Full Session') ?></h5><?php endif; ?>
                             <video width="100%" controls <?php if($thumb = $stream->getThumbnailUrl()): ?>poster="<?= Html::encode($thumb) ?>"<?php endif; ?> style="background: #000; border-radius: 8px;" preload="metadata">
                                <source src="<?= Html::encode($stream->recording_url) ?>" type="video/mp4">
                                <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Your browser does not support the video tag.') ?>
                            </video> 
                            <div class="text-right" style="margin-top: 5px;">
                                <a href="<?= Html::encode($stream->recording_url) ?>" target="_blank" class="btn btn-default btn-sm">
                                    <i class="fa fa-external-link"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Open in new tab') ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($hasHighlights && !$hasRecording): ?>
                        <!-- Case handled above by showing highlights player -->
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- DOWNLOADS TAB -->
                <div role="tabpanel" class="tab-pane <?= ($activeTab == 'downloads') ? 'active' : '' ?>" id="tab-downloads">
                    <?php if ($isExpired): ?>
                        <div class="alert alert-warning" style="margin-bottom: 20px;">
                            <i class="fa fa-exclamation-triangle"></i> 
                            <?= Yii::t('JitsiMeetCloud8x8Module.base', 'The download period for this stream has expired (24 hours). Files are no longer available from the cloud cache.') ?>
                        </div>
                    <?php endif; ?>

                    <div class="list-group">
                        <?php if ($hasRecording): ?>
                            <?php if (!$isExpired): ?>
                                <a href="<?= Html::encode($stream->recording_url) ?>" class="list-group-item" download target="_blank">
                                    <h4 class="list-group-item-heading"><i class="fa fa-file-video-o"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Full Recording') ?></h4>
                                    <p class="list-group-item-text text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Download the MP4 video file of the entire session.') ?></p>
                                </a>
                            <?php else: ?>
                                <div class="list-group-item disabled" style="opacity: 0.6; background: #f9f9f9;">
                                    <h4 class="list-group-item-heading" style="color: #999;"><i class="fa fa-ban"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Full Recording (Expired)') ?></h4>
                                    <p class="list-group-item-text text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'File no longer available.') ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($hasHighlights): ?>
                            <?php if (!$isExpired): ?>
                                <a href="<?= Html::encode($stream->highlights_url) ?>" class="list-group-item" download target="_blank">
                                    <h4 class="list-group-item-heading"><i class="fa fa-film"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Highlights Video') ?></h4>
                                    <p class="list-group-item-text text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Auto-generated highlights summary.') ?></p>
                                </a>
                            <?php else: ?>
                                <div class="list-group-item disabled" style="opacity: 0.6; background: #f9f9f9;">
                                    <h4 class="list-group-item-heading" style="color: #999;"><i class="fa fa-ban"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Highlights Video (Expired)') ?></h4>
                                    <p class="list-group-item-text text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'File no longer available.') ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($stream->ytstream_url)): ?>
                            <a href="<?= Html::encode($stream->ytstream_url) ?>" class="list-group-item" target="_blank">
                                <h4 class="list-group-item-heading"><i class="fa fa-youtube-play"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'YouTube Stream') ?></h4>
                                <p class="list-group-item-text text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'View the archived stream on YouTube.') ?></p>
                            </a>
                        <?php endif; ?>

                        <?php if ($hasChat): ?>
                                <?php if (!$isExpired): ?>
                                    <a href="<?= Html::encode($stream->chat_log_url) ?>" class="list-group-item" target="_blank" download>
                                        <h4 class="list-group-item-heading"><i class="fa fa-comments-o"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Chat Log') ?></h4>
                                        <p class="list-group-item-text text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Download the full chat history.') ?></p>
                                    </a>
                                <?php else: ?>
                                    <div class="list-group-item disabled" style="opacity: 0.6; background: #f9f9f9;">
                                        <h4 class="list-group-item-heading" style="color: #999;"><i class="fa fa-ban"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Chat Log (Expired)') ?></h4>
                                        <p class="list-group-item-text text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'File no longer available.') ?></p>
                                    </div>
                                <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($hasTranscript): ?>
                            <?php if (!$isExpired): ?>
                                <a href="<?= Html::encode($stream->transcription_url) ?>" class="list-group-item" target="_blank" download>
                                    <h4 class="list-group-item-heading"><i class="fa fa-file-text-o"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Transcription') ?></h4>
                                    <p class="list-group-item-text text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Download text transcription.') ?></p>
                                </a>
                            <?php else: ?>
                                <div class="list-group-item disabled" style="opacity: 0.6; background: #f9f9f9;">
                                    <h4 class="list-group-item-heading" style="color: #999;"><i class="fa fa-ban"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Transcription (Expired)') ?></h4>
                                    <p class="list-group-item-text text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'File no longer available.') ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$hasRecording && !$hasHighlights && !$hasChat && !$hasTranscript && !$hasExtraFiles && empty($stream->ytstream_url)): ?>
                        <div class="text-center text-muted" style="padding: 30px;">
                             <?= Yii::t('JitsiMeetCloud8x8Module.base', 'No media files available for this session.') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SESSION DATA TAB -->
                <?php if ($hasSessionData): ?>
                <div role="tabpanel" class="tab-pane" id="tab-session">
                    <div class="row">
                        <div class="col-md-6">
                            
                            <!-- RELOCATED REACTIONS -->
                            <?php $reactions = $stream->getAggregatedReactions(); ?>
                            <?php if (!empty($reactions)): ?>
                                <h4><i class="fa fa-smile-o"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Reactions') ?></h4>
                                <div class="reactions-container" style="margin-bottom: 20px;">
                                    <?php foreach ($reactions as $emoji => $count): ?>
                                        <span class="badge" style="background: var(--jitsi-card-bg); color: var(--jitsi-text-primary); border: 1px solid var(--jitsi-border-color); font-size: 14px; margin-right: 5px; padding: 5px 10px;">
                                            <?= $emoji ?> <span style="margin-left: 5px; font-weight: bold; color: var(--primary);"><?= $count ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                                <hr>
                            <?php endif; ?>

                            <h4><i class="fa fa-users"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Participation') ?></h4>
                            <table class="table">
                                <tr>
                                    <td><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Peak Participants') ?></td>
                                    <td><strong><?= $stream->participant_count ?></strong></td>
                                </tr>
                                <tr>
                                    <td><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Active Participants (Mic/Cam)') ?></td>
                                    <td><strong><?= $stream->active_count ?></strong></td>
                                </tr>
                            </table>
                            
                            <!-- Avatars -->
                            <?php $participants = $stream->getRecentParticipants(10); ?>
                            <?php if (!empty($participants)): ?>
                                <div style="margin-top: 10px;">
                                    <label><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Members who Participated') ?></label>
                                    <div class="avatar-stack" style="margin-left: 2px;">
                                        <?php foreach ($participants as $p): ?>
                                            <div class="avatar-stack-item" title="<?= Html::encode($p['name']) ?>">
                                                <?php if ($p['user']): ?>
                                                    <?= Image::widget(['user' => $p['user'], 'width' => 24, 'link' => true]) ?>
                                                <?php else: ?>
                                                    <img src="<?= Yii::$app->view->theme->baseUrl ?>/img/default_user.jpg" alt="<?= Html::encode($p['name']) ?>">
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                         <div class="col-md-12">
                            <hr>

                            <?php if (!empty($polls)): ?>
                                <h4><i class="fa fa-question-circle"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Poll Results') ?></h4>
                                <?php foreach ($polls as $poll): ?>
                                    <?php 
                                        $options = $poll['options'] ?? [];
                                        $votes = $poll['votes'] ?? [];
                                        $totalVotes = count($votes);
                                        
                                        // Aggregate
                                        $results = [];
                                        foreach ($options as $opt) $results[$opt['key']] = 0;
                                        foreach ($votes as $vote) {
                                            foreach ($vote['keys'] as $k) {
                                                if (isset($results[$k])) $results[$k]++;
                                            }
                                        }
                                    ?>
                                    <div class="panel panel-default" style="margin-bottom: 10px;">
                                        <div class="panel-heading" style="padding: 10px; font-weight: bold;">
                                            <?= Html::encode($poll['question']) ?>
                                            <span class="pull-right badge"><?= $totalVotes ?> votes</span>
                                        </div>
                                        <ul class="list-group">
                                            <?php foreach ($options as $opt): 
                                                $count = $results[$opt['key']] ?? 0;
                                                $percent = ($totalVotes > 0) ? round(($count / $totalVotes) * 100) : 0;
                                            ?>
                                            <li class="list-group-item" style="border: none; padding: 8px 15px;">
                                                <div style="margin-bottom: 2px;">
                                                    <?= Html::encode($opt['name']) ?>
                                                    <span class="pull-right text-muted" style="font-size: 11px;"><?= $count ?> (<?= $percent ?>%)</span>
                                                </div>
                                                <div class="progress" style="height: 6px; margin-bottom: 0;">
                                                    <div class="progress-bar progress-bar-info" role="progressbar" 
                                                         style="width: <?= $percent ?>%; background-color: var(--primary);"></div>
                                                </div>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'No polls created during this session.') ?></p>
                            <?php endif; ?>
                         </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- CHAT LOG TAB -->
                <?php if ($hasChat): ?>
                <div role="tabpanel" class="tab-pane" id="tab-chat">
                     <div class="row">
                        <div class="col-md-12">
                            <?php if (!empty($chatMessages) && count($chatMessages) > 0): ?>
                            <div class="chat-history-container" style="max-height: 500px; overflow-y: auto; border: 1px solid var(--jitsi-border-color); border-radius: 4px; padding: 15px; background: var(--jitsi-card-bg);">
                                <ul class="media-list">
                                    <?php foreach ($chatMessages as $msg): 
                                        // Sender Name Parsing
                                        $sender = $msg['nick'] 
                                               ?? $msg['displayName'] 
                                               ?? $msg['name'] 
                                               ?? $msg['endpointName'] 
                                               ?? Yii::t('JitsiMeetCloud8x8Module.base', 'Unknown Participant');
                                               
                                        $text = $msg['message'] 
                                             ?? $msg['text'] 
                                             ?? $msg['body'] 
                                             ?? $msg['content'] 
                                             ?? $msg['msg'] 
                                             ?? '';
                                        $time = isset($msg['timestamp']) ? Yii::$app->formatter->asTime(date('Y-m-d H:i:s', $msg['timestamp'] / 1000), 'short') : '';
                                        
                                        // Skip empty messages
                                        if (empty(trim($text))) continue;

                                        // GIF/Image cleanup: Handle 8x8 specific "gif[url]" format
                                        // This often comes as "gif[https://...]"
                                        if (preg_match('/^gif\[(https?:\/\/\S+)\]$/i', trim($text), $matches)) {
                                            $text = $matches[1];
                                        }

                                        // Image Parsing (Convert URLs ending in image extensions to img tags)
                                        // Security Fix: Use preg_split to avoid XSS when parsing URLs from encoded text
                                        $parts = preg_split('/(https?:\/\/\S+\.(?:png|jpg|jpeg|gif|webp|svg)(?:\?\S*)?)/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
                                        $text = '';
                                        foreach ($parts as $i => $part) {
                                            if ($i % 2 === 0) {
                                                $text .= Html::encode($part);
                                            } else {
                                                $url = Html::encode($part);
                                                $text .= '<br><a href="' . $url . '" target="_blank"><img src="' . $url . '" style="max-width: 100%; max-height: 200px; border-radius: 8px; margin-top: 5px; border: 1px solid #444;" /></a><br>';
                                            }
                                        }

                                        // Emoji Shortcode Mapping
                                        $chatEmojiMap = [
                                            ':smile:' => '😊',
                                            ':smiley:' => '😃',
                                            ':grinning:' => '😀',
                                            ':beer:' => '🍺',
                                            ':beers:' => '🍻',
                                            ':angel:' => '😇',
                                            ':love:' => '❤️',
                                            ':heart:' => '❤️',
                                            ':thumbsup:' => '👍',
                                            ':thumbsdown:' => '👎',
                                            ':clap:' => '👏',
                                            ':fire:' => '🔥',
                                            ':joy:' => '😂',
                                            ':laugh:' => '😆',
                                            ':sad:' => '😢',
                                            ':cry:' => '😭',
                                            ':angry:' => '😠',
                                            ':surprised:' => '😮',
                                            ':wink:' => '😉',
                                            ':tongue:' => 'muk',
                                            ':cool:' => '😎',
                                            ':party:' => '🎉',
                                            // Add more common Jitsi/Generic ones
                                        ];
                                        
                                        $text = strtr($text, $chatEmojiMap);
                                        
                                        // Determine Avatar
                                        $avatarUrl = $msg['avatar'] ?? null;
                                    ?>
                                    <li class="media" style="margin-top: 10px; border-bottom: 1px solid var(--jitsi-border-color); padding-bottom: 10px;">
                                        <div class="media-left">
                                            <?php if ($avatarUrl): ?>
                                                <img class="media-object img-circle" src="<?= Html::encode($avatarUrl) ?>" alt="<?= Html::encode($sender) ?>" style="width: 32px; height: 32px;">
                                            <?php else: ?>
                                                <?php 
                                                    // Antigravity Fix: Try to resolve HumHub user safely
                                                    $hhUser = null;
                                                    if (!empty($email)) {
                                                        $hhUser = \humhub\modules\user\models\User::findOne(['email' => $email]);
                                                    }
                                                    if (!$hhUser && !empty($sender)) {
                                                        // Try by username (display_name is not a column)
                                                        $hhUser = \humhub\modules\user\models\User::findOne(['username' => $sender]);
                                                    }
                                                ?>
                                                <?php if ($hhUser): ?>
                                                    <?= Image::widget(['user' => $hhUser, 'width' => 32, 'link' => true]) ?>
                                                <?php else: ?>
                                                    <img class="media-object img-circle" src="<?= Yii::$app->view->theme->baseUrl ?>/img/default_user.jpg" alt="<?= Html::encode($sender) ?>" style="width: 32px; height: 32px;">
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="media-body">
                                            <h5 class="media-heading" style="font-size: 14px; font-weight: bold; margin-bottom: 2px;">
                                                <?= Html::encode($sender) ?> 
                                                <small class="pull-right text-muted" style="font-size: 11px;"><?= $time ?></small>
                                            </h5>
                                            <div style="font-size: 13px; line-height: 1.4; color: var(--jitsi-text-primary);">
                                                <?= nl2br($text) // Note: $text is already encoded above, except for the image tags we injected ?>
                                            </div>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Chat log file exists but contains no readable messages.') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- FEEDBACK TAB -->
                <?php if ($hasFeedback): ?>
                <div role="tabpanel" class="tab-pane" id="tab-feedback">
                    <div class="row">
                        <div class="col-md-12">
                            <h4><i class="fa fa-star"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'User Feedback') ?></h4>
                            <div class="list-group">
                                <?php foreach ($feedback as $fb): ?>
                                    <div class="list-group-item">
                                        <div class="row">
                                             <div class="col-xs-2 text-center">
                                                 <h2 style="margin: 0; color: #f1c40f;"><?= $fb['rating'] ?><small>/5</small></h2>
                                                 <div class="rating-stars" style="color: #f1c40f;">
                                                     <?php for($i=1; $i<=5; $i++): ?>
                                                         <i class="fa fa-star<?= ($i <= $fb['rating']) ? '' : '-o' ?>"></i>
                                                     <?php endfor; ?>
                                                 </div>
                                             </div>
                                             <div class="col-xs-10">
                                                 <p class="list-group-item-text" style="font-size: 14px; margin-top: 5px;">
                                                     <?= !empty($fb['comment']) ? Html::encode($fb['comment']) : '<i>No comment provided</i>' ?>
                                                 </p>
                                                 <small class="text-muted">
                                                     <?= Yii::$app->formatter->asDatetime($fb['timestamp'], 'short') ?> 
                                                     <?php if(isset($fb['userId']) && $fb['userId'] != 'unknown'): ?>
                                                         <?php 
                                                            $fbUser = \humhub\modules\user\models\User::findOne($fb['userId']);
                                                            $fbName = $fbUser ? $fbUser->displayName : 'User ID: ' . $fb['userId'];
                                                         ?>
                                                         &bull; <?= Html::encode($fbName) ?>
                                                     <?php endif; ?>
                                                 </small>
                                             </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>

        <?php endif; ?>

    </div>
    
    <div class="modal-footer">
        <?php 
        // Delete Button Logic (Creator/Admin)
        $canDelete = false;
        if (!Yii::$app->user->isGuest) {
            if ($stream->creator_id == Yii::$app->user->id) {
                $canDelete = true;
            } elseif (Yii::$app->user->isAdmin()) {
                $canDelete = true;
            } elseif ($stream->space && $stream->space->isAdmin()) {
                $canDelete = true;
            } elseif ($stream->calendarEntry && $stream->calendarEntry->content->container->can(\humhub\modules\content\permissions\ManageContent::class)) {
                $canDelete = true;
            }
        }
        
        if ($canDelete): ?>
            <?= Html::a(Yii::t('JitsiMeetCloud8x8Module.base', 'Delete'), \yii\helpers\Url::to(['/jitsi-meet-cloud-8x8/room/delete', 'id' => $stream->id]), [
                'class' => 'btn btn-danger pull-left',
                'data-method' => 'post',
                'data-confirm' => Yii::t('JitsiMeetCloud8x8Module.base', 'Are you sure you want to delete this stream?'),
            ]) ?>
        <?php endif; ?>

        <?= ModalButton::cancel(Yii::t('JitsiMeetCloud8x8Module.base', 'Close')) ?>
    </div>

<?php ModalDialog::end(); ?>

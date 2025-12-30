<?php
use humhub\libs\Html;
use yii\helpers\Url;
use humhub\widgets\ModalButton;

/* @var $stream \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream */

$expirationTime = null;
if (!empty($stream->end_time)) {
    // 24 hours after end time
    $expirationTime = strtotime($stream->end_time) + (24 * 60 * 60);
}
?>

<div class="modal-dialog modal-dialog-medium animated fadeIn">
    <div class="modal-content jitsi-stream-details">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" data-modal-close aria-hidden="true">&times;</button>
            <h4 class="modal-title" id="myModalLabel">
                <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Stream Recording & Downloads'); ?>
            </h4>
        </div>
        <div class="modal-body">
            <div class="text-center">
                <h3><?= Html::encode($stream->title ?: $stream->room_name) ?></h3>
                <p class="text-muted" style="margin-bottom: 5px;">
                    <small>Room name: <?= Html::encode($stream->room_name) ?></small>
                </p>
                <p class="text-muted">
                    Ended: <?= Yii::$app->formatter->asDatetime($stream->end_time, 'medium') ?>
                    <br>
                    Duration: <?= $stream->getDuration() ?>
                </p>

                <?php if ($expirationTime && time() < $expirationTime): ?>
                    <div class="alert alert-warning" style="margin-top: 15px;">
                        <strong>Downloads Expire In:</strong> <span id="expiration-timer">Calculcating...</span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger" style="margin-top: 15px;">
                        <strong>Downloads Expired</strong>
                    </div>
                <?php endif; ?>
            </div>

            <hr>

            <?php
            use humhub\widgets\Tabs;
            
            // 1. Capture Downloads Content
            ob_start();
            ?>
            <div class="list-group">
                <?php 
                $isExpired = ($expirationTime && time() >= $expirationTime);
                $disabledStyle = $isExpired ? 'pointer-events: none; opacity: 0.5; background-color: #f5f5f5;' : '';
                ?>
                <?php if (!empty($stream->recording_url)): ?>
                    <a href="<?= Html::encode($stream->recording_url) ?>" target="_blank" class="list-group-item" style="<?= $disabledStyle ?>">
                        <i class="fa fa-video-camera fa-fw" style="margin-right: 10px;"></i> 
                        Download Video Recording
                        <span class="pull-right"><i class="fa fa-download"></i></span>
                    </a>
                <?php elseif ($stream->has_recording && !$isExpired): ?>
                    <div class="list-group-item disabled" style="color: #999;">
                        <i class="fa fa-spinner fa-fw fa-pulse" style="margin-right: 10px;"></i> 
                        Processing Video Recording...
                    </div>
                <?php endif; ?>

                <?php if (!empty($stream->highlights_url)): ?>
                    <a href="<?= Html::encode($stream->highlights_url) ?>" target="_blank" class="list-group-item" style="<?= $disabledStyle ?>">
                        <i class="fa fa-film fa-fw" style="margin-right: 10px;"></i>
                        Download Highlights (90s)
                        <span class="pull-right"><i class="fa fa-download"></i></span>
                    </a>
                <?php elseif ($stream->has_recording && !$isExpired): ?>
                    <div class="list-group-item disabled" style="color: #999;">
                        <i class="fa fa-spinner fa-fw fa-pulse" style="margin-right: 10px;"></i> 
                        Processing Highlights...
                    </div>
                <?php endif; ?>

                <?php if (!empty($stream->transcription_url)): ?>
                    <a href="<?= Html::encode($stream->transcription_url) ?>" target="_blank" class="list-group-item" style="<?= $disabledStyle ?>">
                        <i class="fa fa-file-text-o fa-fw" style="margin-right: 10px;"></i>
                        Download Transcript
                        <span class="pull-right"><i class="fa fa-download"></i></span>
                    </a>
                <?php endif; ?>
                <?php if (!empty($stream->chat_log_url)): ?>
                    <a href="<?= Html::encode($stream->chat_log_url) ?>" target="_blank" class="list-group-item" style="<?= $disabledStyle ?>">
                        <i class="fa fa-comments-o fa-fw" style="margin-right: 10px;"></i>
                        Download Chat Log
                        <span class="pull-right"><i class="fa fa-download"></i></span>
                    </a>
                <?php endif; ?>
                <?php 
                $files = $stream->getFiles();
                if (!empty($files)): 
                    foreach($files as $index => $fileUrl):
                ?>
                    <a href="<?= Html::encode($fileUrl) ?>" target="_blank" class="list-group-item" style="<?= $disabledStyle ?>">
                        <i class="fa fa-file-o fa-fw" style="margin-right: 10px;"></i>
                        Download File <?= $index + 1 ?>
                        <span class="pull-right"><i class="fa fa-download"></i></span>
                    </a>
                <?php endforeach; endif; ?>
                <?php if (empty($stream->recording_url) && empty($stream->highlights_url) && empty($stream->transcription_url) && empty($stream->chat_log_url) && empty($files) && $isExpired): ?>
                    <div class="text-center text-muted" style="padding: 20px;">
                        No downloads available for this stream.
                    </div>
                <?php endif; ?>
            </div>
            <?php
            $downloadsContent = ob_get_clean();

            // 2. Capture Chat Log Content
            $chatTabContent = '';
            if (!empty($chatLogContent)) {
                 $chatData = json_decode($chatLogContent, true);
                 $messages = $chatData['messages'] ?? [];
                 
                 $chatTabContent = '<div style="max-height: 400px; overflow-y: auto; background: #f9f9f9; padding: 15px; border: 1px solid #eee; border-radius: 4px;">';
                 
                 if (!empty($messages) && is_array($messages)) {
                     $chatTabContent .= '<ul class="media-list">';
                     foreach ($messages as $msg) {
                         $name = Html::encode($msg['name'] ?? 'Unknown');
                         $text = Html::encode($msg['content'] ?? '');
                         $time = isset($msg['timestamp']) ? Yii::$app->formatter->asTime($msg['timestamp'] / 1000) : ''; // 8x8 uses ms timestamps? Check logic.
                         // Sometimes timestamp is ISO string or ms. 8x8 usually uses ms integers in these logs based on previous samples.
                         
                         $chatTabContent .= '<li class="media" style="margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">';
                         $chatTabContent .= '<div class="media-body">';
                         $chatTabContent .= '<h6 class="media-heading" style="font-weight: bold; color: #555;">' . $name . ' <small class="pull-right text-muted">' . $time . '</small></h6>';
                         $chatTabContent .= '<p style="margin: 0;">' . $text . '</p>';
                         $chatTabContent .= '</div>';
                         $chatTabContent .= '</li>';
                     }
                     $chatTabContent .= '</ul>';
                 } else {
                     // Fallback if parsing fails or structure differs
                     $chatTabContent .= '<pre style="white-space: pre-wrap; word-wrap: break-word; background: transparent; border: none;">' . Html::encode($chatLogContent) . '</pre>';
                 }
                 $chatTabContent .= '</div>';
            }

            // 3. Capture Watch Tab Content
            ob_start();
            ?>
            <div style="padding: 15px;">
                <?php if (!empty($stream->recording_url)): ?>
                    <div style="margin-bottom: 20px;">
                        <h5 style="font-weight: bold; color: #555; margin-bottom: 10px;">Full Recording</h5>
                        <div class="embed-responsive embed-responsive-16by9" style="background: #000; border-radius: 4px;">
                            <video class="embed-responsive-item" controls controlsList="nodownload">
                                <source src="<?= Html::encode($stream->recording_url) ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    </div>
                <?php elseif ($stream->has_recording && !$isExpired): ?>
                    <div class="alert alert-info">
                        <i class="fa fa-spinner fa-pulse"></i> Full recording is still processing...
                    </div>
                <?php endif; ?>

                <?php if (!empty($stream->highlights_url)): ?>
                    <div style="margin-bottom: 20px;">
                        <h5 style="font-weight: bold; color: #555; margin-bottom: 10px;">Highlights (90s Segment)</h5>
                        <div class="embed-responsive embed-responsive-16by9" style="background: #000; border-radius: 4px;">
                            <video class="embed-responsive-item" controls controlsList="nodownload">
                                <source src="<?= Html::encode($stream->highlights_url) ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    </div>
                <?php elseif (!$isExpired): ?>
                     <!-- Don't show highlights placeholder if main recording is also processing, to avoid clutter. Or show if you prefer. -->
                <?php endif; ?>

                <?php if (!empty($screenSharingContent) && is_array($screenSharingContent)): ?>
                    <div style="margin-bottom: 20px;">
                        <h5 style="font-weight: bold; color: #555; margin-bottom: 10px;">Screen Sharing History</h5>
                        <div class="row" style="display: flex; flex-wrap: wrap;">
                        <?php foreach ($screenSharingContent as $scIdx => $scUrl): 
                             // Defensive check: if $scUrl is an array, try to find 'url' or 'link' key, otherwise ignore
                             $imgSrc = is_string($scUrl) ? $scUrl : ($scUrl['url'] ?? $scUrl['link'] ?? null);
                             if (!$imgSrc) continue;
                        ?>
                            <div class="col-xs-6 col-md-4" style="margin-bottom: 15px;">
                                <a href="<?= Html::encode($imgSrc) ?>" target="_blank" class="thumbnail" style="display: block; margin-bottom: 0;">
                                    <img src="<?= Html::encode($imgSrc) ?>" alt="Screenshot <?= $scIdx + 1 ?>" style="width: 100%; height: auto; display: block;">
                                </a>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($stream->recording_url) && empty($stream->highlights_url) && empty($screenSharingContent) && $isExpired): ?>
                    <div class="text-center text-muted" style="padding: 20px;">
                        No video or screenshots available to watch.
                    </div>
                <?php endif; ?>
            </div>
            <?php
            $watchTabContent = ob_get_clean();

            // 4. Capture Session Data (Polls/Reactions)
            ob_start();
            ?>
             <div class="list-group">
                <!-- Reactions -->
                <?php if (!empty($stream->reactions)): ?>
                     <div class="list-group-item">
                        <h5 class="list-group-item-heading" style="margin-bottom: 10px;">
                             <i class="fa fa-smile-o fa-fw" style="margin-right: 5px;"></i> Reactions
                        </h5>
                        <div style="margin-top: 10px;">
                        <?php 
                            $reactionsData = json_decode($stream->reactions, true);
                            if (is_array($reactionsData)):
                        ?>
                            <ul class="list-unstyled" style="margin-left: 10px;">
                                <?php foreach ($reactionsData as $reaction): ?>
                                    <?php 
                                        $emoji = '🙂';
                                        switch ($reaction['reaction'] ?? '') {
                                            case 'like': $emoji = '👍'; break;
                                            case 'thumbsup': $emoji = '👍'; break;
                                            case 'claps': $emoji = '👏'; break;
                                            case 'applause': $emoji = '👏'; break;
                                            case 'smile': $emoji = '😄'; break;
                                            case 'surprised': $emoji = '😮'; break;
                                            case 'silent': $emoji = '😶'; break;
                                            case 'silence': $emoji = '😶'; break;
                                            case 'boo': $emoji = '👎'; break;
                                            case 'love': $emoji = '❤️'; break;
                                            case 'laugh': $emoji = '😂'; break;
                                            default: $emoji = '🙂';
                                        }
                                        $name = Html::encode($reaction['participantName'] ?? 'Unknown');
                                    ?>
                                    <li style="margin-bottom: 5px;">
                                        <span style="display: inline-block; width: 20px; text-align: center; margin-right: 5px;"><?= $emoji ?></span> 
                                        <strong><?= $name ?></strong> 
                                        <span class="text-muted" style="font-size: 10px;">
                                            (<?= isset($reaction['timestamp']) ? Yii::$app->formatter->asTime($reaction['timestamp'] / 1000) : '' ?>)
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <pre><?= Html::encode($stream->reactions) ?></pre>
                        <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Polls -->
                <?php 
                if (!empty($stream->polls)) {
                     $pollsData = json_decode($stream->polls, true);
                     if (!empty($pollsData)) {
                        foreach ($pollsData as $pollId => $poll) {
                            $results = [];
                            $totalVotes = 0;
                            foreach ($poll['options'] as $opt) {
                                $results[$opt['key']] = 0;
                            }
                            if (isset($poll['votes'])) {
                                foreach ($poll['votes'] as $voterId => $vote) {
                                    foreach ($vote['keys'] as $k) {
                                        if (isset($results[$k])) {
                                            $results[$k]++;
                                            $totalVotes++;
                                        } elseif (array_key_exists($k, $results)) {
                                            $results[$k]++;
                                            $totalVotes++;
                                        }
                                    }
                                }
                            }
                            ?>
                            <div class="list-group-item">
                                <h5 class="list-group-item-heading" style="margin-bottom: 10px;">
                                    <i class="fa fa-bar-chart fa-fw" style="margin-right: 5px;"></i> Poll: <?= Html::encode($poll['question']) ?>
                                </h5>
                                <div style="margin-top: 10px;">
                                    <ul class="list-unstyled" style="margin-left: 10px;">
                                        <?php foreach ($poll['options'] as $opt): 
                                            $count = $results[$opt['key']] ?? 0;
                                            $percent = $totalVotes > 0 ? round(($count / $totalVotes) * 100) : 0;
                                        ?>
                                        <li style="margin-bottom: 8px;">
                                            <strong><?= Html::encode($opt['name']) ?></strong>
                                            <span class="pull-right text-muted"><?= $count ?> votes (<?= $percent ?>%)</span>
                                            <div class="progress" style="height: 5px; margin-bottom:0;">
                                                <div class="progress-bar" role="progressbar" style="width: <?= $percent ?>%; background-color: #2196F3;"></div>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            <?php
                        }
                     }
                }
                ?>
            </div>
            <?php
            $sessionDataContent = ob_get_clean();
            
            // Render Tabs
            echo Tabs::widget([
                'items' => [
                    [
                        'label' => 'Downloads',
                        'content' => $downloadsContent,
                        'active' => true,
                    ],
                    [
                        'label' => 'Chat Log',
                        'content' => $chatTabContent,
                        'visible' => !empty($chatTabContent),
                    ],
                    [
                        'label' => 'Watch',
                        'content' => $watchTabContent,
                        'visible' => (!$isExpired && ($stream->has_recording || !empty($stream->recording_url) || !empty($stream->highlights_url) || !empty($screenSharingContent))),
                    ],
                    [
                        'label' => 'Session Data',
                        'content' => $sessionDataContent,
                        'visible' => (!empty($stream->reactions) || !empty($stream->polls)),
                    ],
                ],
            ]);
            ?>

        </div>
        <div class="modal-footer">
            <?= ModalButton::cancel('Close') ?>
        </div>
    </div>
</div>

<script>
    (function() {
        var expirationTimestamp = <?= $expirationTime ? $expirationTime : 0 ?>;
        
        function updateTimer() {
            var now = Math.floor(Date.now() / 1000);
            var distance = expirationTimestamp - now;
            
            if (distance < 0) {
                $('#expiration-timer').parent().removeClass('alert-warning').addClass('alert-danger');
                $('#expiration-timer').parent().html('<strong>Downloads Expired</strong>');
                clearInterval(timerInterval);
                return;
            }
            
            var hours = Math.floor(distance / 3600);
            var minutes = Math.floor((distance % 3600) / 60);
            var seconds = Math.floor(distance % 60);
            
            $('#expiration-timer').text(
                hours + "h " + minutes + "m " + seconds + "s "
            );
        }
        
        if (expirationTimestamp > 0) {
            updateTimer();
            var timerInterval = setInterval(updateTimer, 1000);

            // Cleanup when modal is closed
            $(document).one('hidden.bs.modal', '#globalModal', function () {
                clearInterval(timerInterval);
            });
        }
    })();
</script>

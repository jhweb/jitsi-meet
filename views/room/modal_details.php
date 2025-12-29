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
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" data-modal-close aria-hidden="true">&times;</button>
            <h4 class="modal-title" id="myModalLabel">
                <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Stream Recording & Downloads'); ?>
            </h4>
        </div>
        <div class="modal-body">
            <div class="text-center">
                <h3><?= Html::encode($stream->room_name) ?></h3>
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

            <div class="list-group">
                <?php 
                $isExpired = ($expirationTime && time() >= $expirationTime);
                $disabledStyle = $isExpired ? 'pointer-events: none; opacity: 0.5; background-color: #f5f5f5;' : '';
                ?>

                <!-- Watch Video -->
                <?php if (!empty($stream->recording_url)): ?>
                    <a href="<?= Html::encode($stream->recording_url) ?>" target="_blank" class="list-group-item" style="<?= $disabledStyle ?>">
                        <i class="fa fa-video-camera" style="margin-right: 10px;"></i> 
                        Watch Video Recording
                        <span class="pull-right"><i class="fa fa-external-link"></i></span>
                    </a>
                <?php endif; ?>

                <!-- Transcript -->
                <?php if (!empty($stream->transcription_url)): ?>
                    <a href="<?= Html::encode($stream->transcription_url) ?>" target="_blank" class="list-group-item" style="<?= $disabledStyle ?>">
                        <i class="fa fa-file-text-o" style="margin-right: 10px;"></i>
                        Download Transcript
                        <span class="pull-right"><i class="fa fa-download"></i></span>
                    </a>
                <?php endif; ?>

                <!-- Chat Log -->
                <?php if (!empty($stream->chat_log_url)): ?>
                    <a href="<?= Html::encode($stream->chat_log_url) ?>" target="_blank" class="list-group-item" style="<?= $disabledStyle ?>">
                        <i class="fa fa-comments-o" style="margin-right: 10px;"></i>
                        Download Chat Log
                        <span class="pull-right"><i class="fa fa-download"></i></span>
                    </a>
                <?php endif; ?>
                
                <!-- Files -->
                <?php 
                $files = $stream->getFiles();
                if (!empty($files)): 
                    foreach($files as $index => $fileUrl):
                ?>
                    <a href="<?= Html::encode($fileUrl) ?>" target="_blank" class="list-group-item" style="<?= $disabledStyle ?>">
                        <i class="fa fa-file-o" style="margin-right: 10px;"></i>
                        Download File <?= $index + 1 ?>
                        <span class="pull-right"><i class="fa fa-download"></i></span>
                    </a>
                <?php endforeach; endif; ?>

                <!-- Reactions -->
                <?php if (!empty($stream->reactions)): ?>
                     <div class="list-group-item">
                        <div style="cursor: pointer;" data-toggle="collapse" data-target="#reactions-list">
                            <h5 class="list-group-item-heading" style="margin-bottom: 0;">
                                <i class="fa fa-smile-o fa-fw" style="margin-right: 5px;"></i> Reactions
                                <span class="pull-right"><i class="fa fa-chevron-down"></i></span>
                            </h5>
                        </div>
                        <div id="reactions-list" class="collapse list-group-item-text" style="margin-top: 10px;">
                        <?php 
                            $reactionsData = json_decode($stream->reactions, true);
                            if (is_array($reactionsData)):
                        ?>
                            <ul class="list-unstyled" style="margin-left: 20px;">
                                <?php foreach ($reactionsData as $reaction): ?>
                                    <?php 
                                        $emoji = '🙂';
                                        // Map 8x8 reactions to emojis or icons
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
                            // Calculate Results
                            $results = [];
                            $totalVotes = 0;
                            // Initialize counts
                            foreach ($poll['options'] as $opt) {
                                $results[$opt['key']] = 0;
                            }
                            // Tally votes
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
                                <div style="cursor: pointer;" data-toggle="collapse" data-target="#poll-<?= $pollId ?>">
                                    <h5 class="list-group-item-heading" style="margin-bottom: 0;">
                                        <i class="fa fa-bar-chart fa-fw" style="margin-right: 5px;"></i> Poll: <?= Html::encode($poll['question']) ?>
                                         <span class="pull-right"><i class="fa fa-chevron-down"></i></span>
                                    </h5>
                                </div>
                                <div id="poll-<?= $pollId ?>" class="collapse list-group-item-text" style="margin-top: 10px;">
                                    <ul class="list-unstyled" style="margin-left: 20px;">
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


                 <?php if (empty($stream->recording_url) && empty($stream->transcription_url) && empty($stream->chat_log_url) && empty($files)): ?>
                    <div class="text-center text-muted" style="padding: 20px;">
                        No downloads available for this stream.
                    </div>
                 <?php endif; ?>

            </div>

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

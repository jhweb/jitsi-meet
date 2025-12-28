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
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
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
                <!-- Watch Video -->
                <?php if (!empty($stream->recording_url)): ?>
                    <a href="<?= Html::encode($stream->recording_url) ?>" target="_blank" class="list-group-item">
                        <i class="fa fa-video-camera" style="margin-right: 10px;"></i> 
                        Watch Video Recording
                        <span class="pull-right"><i class="fa fa-external-link"></i></span>
                    </a>
                <?php endif; ?>

                <!-- Transcript -->
                <?php if (!empty($stream->transcription_url)): ?>
                    <a href="<?= Html::encode($stream->transcription_url) ?>" target="_blank" class="list-group-item">
                        <i class="fa fa-file-text-o" style="margin-right: 10px;"></i>
                        Download Transcript
                        <span class="pull-right"><i class="fa fa-download"></i></span>
                    </a>
                <?php endif; ?>

                <!-- Chat Log -->
                <?php if (!empty($stream->chat_log_url)): ?>
                    <a href="<?= Html::encode($stream->chat_log_url) ?>" target="_blank" class="list-group-item">
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
                    <a href="<?= Html::encode($fileUrl) ?>" target="_blank" class="list-group-item">
                        <i class="fa fa-file-o" style="margin-right: 10px;"></i>
                        Download File <?= $index + 1 ?>
                        <span class="pull-right"><i class="fa fa-download"></i></span>
                    </a>
                <?php endforeach; endif; ?>

                <!-- Reactions -->
                <?php if (!empty($stream->reactions)): ?>
                     <div class="list-group-item">
                        <i class="fa fa-smile-o" style="margin-right: 10px;"></i>
                        Reactions
                        <pre style="margin-top: 10px; font-size: 10px;"><?= Html::encode(json_encode(json_decode($stream->reactions), JSON_PRETTY_PRINT)) ?></pre>
                    </div>
                <?php endif; ?>

                 <?php if (empty($stream->recording_url) && empty($stream->transcription_url) && empty($stream->chat_log_url) && empty($files)): ?>
                    <div class="text-center text-muted" style="padding: 20px;">
                        No downloads available for this stream.
                    </div>
                 <?php endif; ?>

            </div>

        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
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
        }
    })();
</script>

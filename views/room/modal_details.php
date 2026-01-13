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
$hasSessionData = (($stream->participant_count > 1) || !empty($stream->reactions));
$hasTranscript = !empty($stream->transcription_url);
$hasExtraFiles = !empty($stream->file_urls);

// Status checks (processing/expired)
$isProcessing = false;
if (!$hasRecording && !$hasHighlights && !$hasChat && $stream->status == \humhubContrib\modules\jitsiMeetCloud8x8\models\JitsiLiveStream::STATUS_ENDED) {
    // If ended recently (< 1 hour) and no data, maybe processing
    if (time() - strtotime($stream->end_time) < 3600) {
        $isProcessing = true;
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
    </div>

    <div class="modal-body">
        
        <?php if ($isProcessing): ?>
            <div class="alert alert-info">
                <i class="fa fa-spinner fa-spin"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Stream data is currently processing. Please check back later.') ?>
            </div>
        <?php else: ?>

            <div class="tab-menu">
                <ul class="nav nav-tabs" role="tablist">
                    <?php if ($hasRecording || $hasHighlights): ?>
                    <li role="presentation" class="active">
                        <a href="#tab-watch" aria-controls="tab-watch" role="tab" data-toggle="tab">
                            <i class="fa fa-play-circle"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Watch Replay') ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li role="presentation" class="<?= (!$hasRecording && !$hasHighlights) ? 'active' : '' ?>">
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
                </ul>
            </div>

            <div class="tab-content" style="padding-top: 20px;">
                
                <!-- WATCH TAB -->
                <?php if ($hasRecording || $hasHighlights): ?>
                <div role="tabpanel" class="tab-pane active" id="tab-watch">
                    <?php if ($hasRecording): ?>
                        <div class="video-container" style="margin-bottom: 20px;">
                            <?php 
                                // Simple HTML5 video or basic link depending on URL format
                                // Assuming .mp4 direct link for now based on previous context, or external player
                            ?>
                             <video width="100%" controls <?php if($thumb = $stream->getThumbnailUrl()): ?>poster="<?= Html::encode($thumb) ?>"<?php endif; ?> style="background: #000; border-radius: 8px; preload="metadata"">
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
                        <div class="alert alert-info">
                            <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Full recording not available. Highlights are available in the Downloads tab.') ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- DOWNLOADS TAB -->
                <div role="tabpanel" class="tab-pane <?= (!$hasRecording && !$hasHighlights) ? 'active' : '' ?>" id="tab-downloads">
                    <div class="list-group">
                        <?php if ($hasRecording): ?>
                            <a href="<?= Html::encode($stream->recording_url) ?>" class="list-group-item" download target="_blank">
                                <h4 class="list-group-item-heading"><i class="fa fa-file-video-o"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Full Recording') ?></h4>
                                <p class="list-group-item-text text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Download the MP4 video file of the entire session.') ?></p>
                            </a>
                        <?php endif; ?>

                        <?php if ($hasHighlights): ?>
                            <a href="<?= Html::encode($stream->highlights_url) ?>" class="list-group-item" download target="_blank">
                                <h4 class="list-group-item-heading"><i class="fa fa-film"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Highlights Video') ?></h4>
                                <p class="list-group-item-text text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Auto-generated highlights summary.') ?></p>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($stream->ytstream_url)): ?>
                            <a href="<?= Html::encode($stream->ytstream_url) ?>" class="list-group-item" target="_blank">
                                <h4 class="list-group-item-heading"><i class="fa fa-youtube-play"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'YouTube Stream') ?></h4>
                                <p class="list-group-item-text text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'View the archived stream on YouTube.') ?></p>
                            </a>
                        <?php endif; ?>

                        <?php if ($hasChat): ?>
                             <?php if ($chatLogContent): ?>
                                <!-- Chat link usually opens raw json/txt -->
                                <a href="<?= Html::encode($stream->chat_log_url) ?>" class="list-group-item" target="_blank" download>
                                    <h4 class="list-group-item-heading"><i class="fa fa-comments-o"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Chat Log') ?></h4>
                                    <p class="list-group-item-text text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Download the full chat history.') ?></p>
                                </a>
                             <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($hasTranscript): ?>
                            <a href="<?= Html::encode($stream->transcription_url) ?>" class="list-group-item" target="_blank" download>
                                <h4 class="list-group-item-heading"><i class="fa fa-file-text-o"></i> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Transcription') ?></h4>
                                <p class="list-group-item-text text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Download text transcription.') ?></p>
                            </a>
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
                        </div>
                         <div class="col-md-12">
                            <hr>
                            <?php 
                                // If we have session stats JSON or logic, display here.
                                // For now, simple placeholder or reactions if available.
                            ?>
                         </div>
                    </div>
                </div>
                <?php endif; ?>

            </div>

        <?php endif; ?>

    </div>
    
    <div class="modal-footer">
        <?= ModalButton::cancel(Yii::t('JitsiMeetCloud8x8Module.base', 'Close')) ?>
    </div>

<?php ModalDialog::end(); ?>

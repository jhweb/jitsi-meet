<?php

use humhub\widgets\Button;
use humhub\modules\ui\form\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;
use humhubContrib\modules\jitsiMeetCloud8x8\assets\ConfigAssets;
use humhubContrib\modules\jitsiMeetCloud8x8\models\SettingsForm;

/* @var $model SettingsForm */

ConfigAssets::register($this);

$module = Yii::$app->getModule('jitsi-meet-cloud-8x8');
$calendarEnabled = $module->isCalendarEnabled();

$isCustomDomain = !in_array($model->jitsiDomain, SettingsForm::DEFAULT_JITSI_DOMAINS, true);
$domainOptions = SettingsForm::defaultJitsiDomainOptions();
$modeOptions = [
    'self_hosted' => Yii::t('JitsiMeetCloud8x8Module.base', 'Self-Hosted Jitsi'),
    'jaas' => Yii::t('JitsiMeetCloud8x8Module.base', '8x8 JaaS (Cloud)'),
];

$connectionOpen = true;
$featuresOpen = false;
$permissionsOpen = false;
?>

<div class="panel panel-default">

    <div class="panel-heading"><?= Yii::t('JitsiMeetCloud8x8Module.base', '<strong>Jitsi</strong> module configuration'); ?></div>

    <div class="panel-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form', 'acknowledge' => true]) ?>

        <?= $form->beginCollapsibleFields(Yii::t('JitsiMeetCloud8x8Module.base', 'Connection'), !$connectionOpen) ?>

        <?= $form->field($model, 'mode')->dropDownList($modeOptions) ?>

        <?php if ($model->mode === 'jaas' && empty($model->jaasWebhookSecret)): ?>
        <div class="alert alert-warning alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Close') ?>"><span aria-hidden="true">&times;</span></button>
            <strong><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Webhook security:') ?></strong>
            <?= Yii::t('JitsiMeetCloud8x8Module.base', 'No webhook secret is configured. All incoming JaaS webhooks are being rejected until you set the Webhook Secret below and save. Copy the same secret from your 8x8 JaaS Console webhook settings.') ?>
        </div>
        <?php endif; ?>

        <?= $form->field($model, 'jitsiDomain')->dropDownList(
            $domainOptions,
            ['prompt' => Yii::t('JitsiMeetCloud8x8Module.base', 'Custom domain')]
        )->hint('') ?>
        <?= $form->field($model, 'jitsiDomain', [
            'options' => ['class' => 'form-group field-settingsform-jitsidomain-custom'],
        ])->textInput([
            'id' => 'settingsform-jitsidomain-custom',
            'value' => $isCustomDomain ? $model->jitsiDomain : '',
        ])->label('') ?>
        <?= $form->field($model, 'roomPrefix') ?>
        <?= $form->field($model, 'menuTitle') ?>

        <?= $form->field($model, 'enableJwt')->checkbox() ?>
        <?= $form->field($model, 'jitsiAppID') ?>
        <?= $form->field($model, 'jitsiAppSecret') ?>

        <?= $form->field($model, 'jaasAppId') ?>
        <?= $form->field($model, 'jaasKid') ?>
        <?= $form->field($model, 'jaasPrivateKeyPath') ?>
        <?= $form->field($model, 'jaasWebhookSecret') ?>
        <?= $form->field($model, 'jaasWebhookDriftTolerance')->textInput(['type' => 'number', 'min' => 0]) ?>
        <?= $form->field($model, 'jaasDomain') ?>

        <div class="form-group jitsi-webhook-url-group">
            <label class="control-label" for="jitsi-webhook-url"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Webhook URL for 8x8 Console') ?></label>
            <div class="input-group">
                <input type="text" class="form-control" id="jitsi-webhook-url" value="<?= Html::encode(Url::to(['/jitsi-meet-cloud-8x8/webhook'], true)) ?>" readonly>
                <span class="input-group-btn">
                    <button
                        class="btn btn-default"
                        type="button"
                        id="jitsi-webhook-url-copy"
                        aria-label="<?= Yii::t('JitsiMeetCloud8x8Module.base', 'Copy webhook URL to clipboard') ?>"
                    ><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Copy') ?></button>
                </span>
            </div>
            <p class="help-block"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Configure this URL in your 8x8 JaaS Console under Webhooks to enable live stream tracking.') ?></p>
        </div>

        <?= $form->endCollapsibleFields() ?>

        <?= $form->beginCollapsibleFields(Yii::t('JitsiMeetCloud8x8Module.base', 'Features'), !$featuresOpen) ?>

        <?= $form->field($model, 'jaasEnableRecording')->checkbox() ?>
        <?= $form->field($model, 'jaasEnableLivestreaming')->checkbox() ?>
        <?= $form->field($model, 'jaasEnableModeration')->checkbox() ?>

        <?= $form->field($model, 'enableLiveStreamWidget')->checkbox() ?>
        <?= $form->field($model, 'liveStreamWidgetTitle') ?>
        <?= $form->field($model, 'liveStreamRoomName') ?>
        <?= $form->field($model, 'entriesPerPage')->textInput(['type' => 'number', 'min' => 1]) ?>

        <?php if (!$calendarEnabled): ?>
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i>
            <?= Yii::t('JitsiMeetCloud8x8Module.base', 'The HumHub Calendar module is required for scheduling features. Please install and enable the Calendar module to use this feature.') ?>
        </div>
        <?php endif; ?>
        <?= $form->field($model, 'enableScheduling')->checkbox(['disabled' => !$calendarEnabled]) ?>

        <?= $form->field($model, 'enableTour')->checkbox() ?>

        <?= $form->endCollapsibleFields() ?>

        <?= $form->beginCollapsibleFields(Yii::t('JitsiMeetCloud8x8Module.base', 'Permission Defaults'), !$permissionsOpen) ?>

        <div class="alert alert-warning">
            <strong><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Security Notice:') ?></strong>
            <?= Yii::t('JitsiMeetCloud8x8Module.base', 'Recording and livestreaming permissions are restricted to administrators only by default. These settings control the default state for users who have the appropriate permissions.') ?>
        </div>

        <?= $form->field($model, 'defaultRecordingEnabled')->checkbox() ?>
        <?= $form->field($model, 'defaultLivestreamingEnabled')->checkbox() ?>
        <?= $form->field($model, 'defaultModerationEnabled')->checkbox() ?>

        <?= $form->endCollapsibleFields() ?>

        <?= Button::save()->submit() ?>
        <?php ActiveForm::end() ?>
    </div>
</div>

<?php if ($model->mode === 'jaas'): ?>
<?php
$keyPath = getenv('HUMHUB_JAAS_PRIVATE_KEY_PATH') ?: $model->jaasPrivateKeyPath;
$keyExists = !empty($keyPath) && file_exists($keyPath);
$keyReadable = $keyExists && is_readable($keyPath);
$keySize = ($keyExists && is_readable($keyPath)) ? @filesize($keyPath) : false;
?>
<div class="panel panel-info">
    <div class="panel-heading">
        <h4><?= Yii::t('JitsiMeetCloud8x8Module.base', 'JaaS Debug Information') ?></h4>
    </div>
    <div class="panel-body">

        <div class="row">
            <div class="col-md-6">
                <h5><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Configuration Status') ?></h5>
                <ul class="list-unstyled">
                    <li>
                        <strong><?= Yii::t('JitsiMeetCloud8x8Module.base', 'App ID:') ?></strong>
                        <?php if (!empty($model->jaasAppId)): ?>
                            <span class="label label-success"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Set') ?></span>
                        <?php else: ?>
                            <span class="label label-danger"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Missing') ?></span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <strong><?= Yii::t('JitsiMeetCloud8x8Module.base', 'API Key:') ?></strong>
                        <?php if (!empty($model->jaasKid)): ?>
                            <span class="label label-success"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Set') ?></span>
                        <?php else: ?>
                            <span class="label label-danger"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Missing') ?></span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <strong><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Private Key Path:') ?></strong>
                        <?php if (!empty($model->jaasPrivateKeyPath)): ?>
                            <span class="label label-success"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Set') ?></span>
                        <?php else: ?>
                            <span class="label label-danger"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Missing') ?></span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>

            <div class="col-md-6">
                <h5><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Private Key File Status') ?></h5>
                <ul class="list-unstyled">
                    <li>
                        <strong><?= Yii::t('JitsiMeetCloud8x8Module.base', 'File Exists:') ?></strong>
                        <?php if ($keyExists): ?>
                            <span class="label label-success"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Yes') ?></span>
                        <?php else: ?>
                            <span class="label label-danger"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'No') ?></span>
                        <?php endif; ?>
                    </li>
                    <li>
                        <strong><?= Yii::t('JitsiMeetCloud8x8Module.base', 'File Readable:') ?></strong>
                        <?php if ($keyReadable): ?>
                            <span class="label label-success"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Yes') ?></span>
                        <?php else: ?>
                            <span class="label label-danger"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'No') ?></span>
                        <?php endif; ?>
                    </li>
                    <?php if ($keySize !== false): ?>
                    <li>
                        <strong><?= Yii::t('JitsiMeetCloud8x8Module.base', 'File Size:') ?></strong>
                        <?= (int) $keySize ?> <?= Yii::t('JitsiMeetCloud8x8Module.base', 'bytes') ?>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h5><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Quick Actions') ?></h5>
                <p>
                    <?= Button::primary(Yii::t('JitsiMeetCloud8x8Module.base', 'Test JWT Generation'))->link(Url::to(['test-jwt'])) ?>
                    <small class="text-muted"><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Generate a test JWT to verify your configuration') ?></small>
                </p>

                <h5><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Setup Instructions') ?></h5>
                <ol>
                    <li><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Place your 8x8 private key file at: {path}', ['path' => '<code>' . Html::encode($keyPath ?: '/var/www/keys/jaas_private.pem') . '</code>']) ?></li>
                    <li><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Set proper permissions: {command}', ['command' => '<code>chmod 600 ' . Html::encode($keyPath ?: '/var/www/keys/jaas_private.pem') . '</code>']) ?></li>
                    <li><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Ensure the file owner matches the PHP process user') ?></li>
                    <li><?= Yii::t('JitsiMeetCloud8x8Module.base', 'Test JWT generation using the button above') ?></li>
                </ol>
            </div>
        </div>

    </div>
</div>
<?php endif; ?>

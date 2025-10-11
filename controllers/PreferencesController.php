<?php

namespace humhubContrib\modules\jitsiMeetCloud8x8\controllers;

use humhub\components\Controller;
use humhubContrib\modules\jitsiMeetCloud8x8\models\UserNotificationPreference;
use humhub\modules\user\models\User;
use Yii;

/**
 * PreferencesController handles user notification preferences
 */
class PreferencesController extends Controller
{
    /**
     * @inheritdoc
     */
    public function getAccessRules()
    {
        return [
            ['allow' => true, 'users' => ['@']]
        ];
    }

    /**
     * Show user notification preferences form
     * @return string
     */
    public function actionIndex()
    {
        $model = UserNotificationPreference::load();
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('JitsiMeetCloud8x8Module.base', 'Your notification preferences have been saved.'));
            return $this->refresh();
        }

        return $this->render('index', [
            'model' => $model
        ]);
    }

    /**
     * Save user notification preferences via AJAX
     * @return array
     */
    public function actionSave()
    {
        Yii::$app->response->format = 'json';

        $model = UserNotificationPreference::load();
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return [
                'success' => true,
                'message' => Yii::t('JitsiMeetCloud8x8Module.base', 'Your notification preferences have been saved.')
            ];
        } else {
            return [
                'success' => false,
                'message' => Yii::t('JitsiMeetCloud8x8Module.base', 'Failed to save notification preferences.'),
                'errors' => $model->getErrors()
            ];
        }
    }

    /**
     * Reset user preferences to defaults
     * @return array
     */
    public function actionReset()
    {
        Yii::$app->response->format = 'json';

        if (UserNotificationPreference::resetToDefaults()) {
            return [
                'success' => true,
                'message' => Yii::t('JitsiMeetCloud8x8Module.base', 'Your notification preferences have been reset to defaults.')
            ];
        } else {
            return [
                'success' => false,
                'message' => Yii::t('JitsiMeetCloud8x8Module.base', 'Failed to reset notification preferences.')
            ];
        }
    }

    /**
     * Get user's current notification preferences
     * @return array
     */
    public function actionGet()
    {
        Yii::$app->response->format = 'json';

        $model = UserNotificationPreference::load();
        
        return [
            'success' => true,
            'preferences' => [
                'notify_24h_before' => $model->notify_24h_before,
                'notify_5min_before' => $model->notify_5min_before,
                'notify_on_start' => $model->notify_on_start,
                'custom_intervals' => $model->custom_intervals,
                'intervals' => $model->getNotificationIntervals()
            ]
        ];
    }
}


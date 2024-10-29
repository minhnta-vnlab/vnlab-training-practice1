<?php
namespace backend\services;

use \common\models\LoginHistory;
use \backend\models\LoginVerification;
use \backend\utils\DateConverter;
use Yii;
use yii\base\Request;

class LoginHistoryService {
    public function createSuccess($user, Request $request) {
        return $this->createWithMessage($user, "login_success", $request);
    }

    public function createWithMessage($user, $message, Request $request) {
        $login_history = new LoginHistory();
        $login_history->user_id = $user->id;
        $login_history->message = $message;
        $remoteIp = Yii::$app->request->headers->get('X-Real-IP');
        $login_history->ip = $remoteIp;
        $login_history->ua = Yii::$app->request->userAgent;
        if(!$login_history->save()) {
            return false;
        }
        return true;
    }
}
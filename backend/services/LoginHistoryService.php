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

    /**
     * Get recent login histories
     * @param \backend\models\User $user
     * @param int $limit
     * @return LoginHistory[]
     */
    public function getRecentLoginHistories($user, $limit = 5, $after = null) {
        $query = LoginHistory::find()
            ->where(['user_id' => $user->id])
            ->orderBy(['login_time'=> SORT_DESC])
            ->limit( $limit );
        if(isset($after)) {
            $query = $query->andWhere(['>', 'login_time', $after]);
        }

        $login_histories = $query->all();

        return $login_histories;
    }

    public function getRecentFailLoginHistories($user, $limit = 5, $after = null) {
        $login_histories = $this->getRecentLoginHistories($user, $limit, $after);
        $id = 0;
        while($id < count($login_histories) && $login_histories[$id]->isFailed()) {
            $id++;
        }

        $failed_login_histories = array_slice( $login_histories, 0, $id );
        return $failed_login_histories;
    }
}
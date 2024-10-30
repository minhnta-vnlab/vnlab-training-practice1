<?php
namespace backend\services;

use backend\models\User;
use \backend\models\UserLock;
use \backend\models\LoginVerification;
use \backend\utils\DateConverter;
use Yii;

class UserLockService {
    public function getByUserEmail($email) {
        $lock = UserLock::find()->joinWith("user")
            ->where(["users.email" => $email])
            ->orderBy(["locked_at" => SORT_DESC])
            ->one();
        return $lock;
    }

    public function getBySecret($secret) {
        $lock = UserLock::find()
            ->where(["unlock_secret" => $secret])
            ->one();
        return $lock;
    }

    public function unlock($lock, $password) {
        if(Yii::$app->security->validatePassword($password, $lock->user->password_hash)) {
            $lock->locked = false;
            $lock->unlock_at = DateConverter::convertToSQL(time());
            $lock->save();

            return true;
        }

        return false;
    }

    public function getLastLockedTime(User $user) {
        $lock = UserLock::find()->where(["user_id" => $user->id])->one();
        if(empty($lock)) {
            return null;
        }
        return $lock->locked_at;
    }
    public function getLastUnlockedTime(User $user) {
        $lock = UserLock::find()->where(["user_id" => $user->id])->one();
        if(empty($lock)) {
            return null;
        }
        return $lock->unlock_at;
    }

    public function createLockByUser(User $user, $reason = "max_try_exceed") {
        $lock = UserLock::find()->where(["user_id" => $user->id])->one() ?? new UserLock();
        if($lock->locked) {
            return false;
        }
        $lock->user_id = $user->id;
        $lock->locked = true;
        $lock->locked_at = DateConverter::convertToSQL(time());
        $lock->locked_reason = $reason;
        $lock->unlock_at = null;
        $lock->unlock_secret = Yii::$app->security->generateRandomString(255);

        if(!$lock->save()) {
            return false;
        }

        return $lock;
    }
}
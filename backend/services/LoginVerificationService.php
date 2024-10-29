<?php
namespace backend\services;

use \backend\models\User;
use \backend\models\LoginVerification;
use \backend\utils\DateConverter;
use Yii;

class LoginVerificationService {
    public function getById($id) {
        return LoginVerification::findOne($id);
    }
    public function createFromUser(User $user) {
        $login_verification = LoginVerification::find()->where(['user_id' => $user->id])->one() ?? new LoginVerification();
        $time = time();
        $exp = $time + env('VERIFICATION_EXP') * 60;

        $login_verification->setAttributes([
            'user_id' => $user->id,
            'verification_method' => $user->two_fa_method,
            'issued_at' => DateConverter::convertToSQL($time),
            'expired_at' => DateConverter::convertToSQL($exp),
            'code' => $user->two_fa_method == 'email' ? Yii::$app->security->generateRandomString(6) : null,
            'active' => 1,
            'num_try' => 0,
        ]);

        if(!$login_verification->save()) {
            return false;
        }

        return $login_verification;
    }

    public function deactivate(LoginVerification $login_verification) {
        $login_verification->active = 0;
        return $login_verification->save();
    }
}
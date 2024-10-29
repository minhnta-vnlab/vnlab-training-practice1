<?php
namespace backend\services;

use backend\models\User;
use common\models\Update2FAForm;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Yii;

class UserService
{
    public function updateTwoFa(Update2FAForm $model)
    {
        $user = User::findOne($model->user_id);
        if (!$user) {
            return ['error' => 'User not found', 'status' => 404];
        }

        if ($model->two_fa_method == 'google_authenticator') {
            $tfa = Yii::$app->tfa;
            $result = $tfa->verifyCode($user->two_fa_secret, $model->code);

            if (!$result) {
                return ['error' => 'Invalid authenticator code', 'status' => 400];
            }
        }

        $user->two_fa_method = $model->two_fa_method ?: null;

        if (!$user->save()) {
            return ['error' => 'Failed to save user', 'status' => 500];
        }

        return ['message' => 'Successfully updated user\'s 2FA method', 'data' => $user];
    }

    public function generateTwoFactorQr(User $user)
    {
        $writer = new SvgWriter();
        $email = $user->email;
        $secret = $user->two_fa_secret;

        $qrCode = new QrCode(
            data: "otpauth://totp/VNLabTraining:$email?secret=$secret",
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        return $writer->write($qrCode)->getString();
    }

    public function getByEmail($email) {
        $user = User::find()->where(['email' => $email])->one();
        return $user;
    }
}

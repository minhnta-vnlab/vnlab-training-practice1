<?php
namespace backend\utils\twofa;
use common\models\LoginVerification;
use Yii;
use RobThree\Auth\TwoFactorAuth;

class AuthenticatorTwoFAVerifier implements ITwoFAVerifier {
    private function __construct() {}
    private static AuthenticatorTwoFAVerifier $instance;
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function verify(LoginVerification $login_verification, string $token): bool {
        $secret = $login_verification->user->two_fa_secret;
        /** @var TwoFactorAuth $tfa */
        $tfa = Yii::$app->tfa;
        return $tfa->verifyCode($secret, $token);
    }
}
<?php
namespace backend\utils\twofa;
use common\models\LoginVerification;
class EmailTwoFAVerifier implements ITwoFAVerifier {
    private function __construct() {}
    private static EmailTwoFAVerifier $instance;
    public static function getInstance() : EmailTwoFAVerifier {
        if(!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function verify(LoginVerification $login_verification, string $token): bool {
        return $login_verification->code === $token;
    }
}
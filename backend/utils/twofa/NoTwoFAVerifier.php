<?php
namespace backend\utils\twofa;

use common\models\LoginVerification;

class NoTwoFAVerifier implements ITwoFAVerifier {
    private function __construct() {}
    private static NoTwoFAVerifier $instance;
    public static function getInstance() : NoTwoFAVerifier {
        if(!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function verify(LoginVerification $login_verification, string|null $token): bool {
        return true;
    }
}
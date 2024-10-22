<?php
namespace backend\utils\twofa;

use common\models\LoginVerification;

class TwoFAVerifier implements ITwoFAVerifier {
    public $method;
    private ITwoFAVerifier $verifier;
    public function __construct(string $method = 'email') {
        $this->method = $method;
    }

    public function useMethod(string $method) {
        $this->method = $method;
        if($this->method == 'email') {
            return $this->useVerifier(EmailTwoFAVerifier::getInstance());
        }
        if($this->method == 'google_authenticator') {
            return $this->useVerifier(AuthenticatorTwoFAVerifier::getInstance());
        }
        throw new \Exception('Method not implemented');
    }

    public function useVerifier(ITwoFAVerifier $verifier) {
        $this->verifier = $verifier;
        return $this;
    }

    public function verify(LoginVerification $login_verification, string $token): bool {
        return $this->verifier->verify($login_verification, $token);
    }
}
<?php
namespace backend\utils\twofa;

use backend\models\LoginVerification;

class TwoFAVerifier implements ITwoFAVerifier {
    public $method;
    private ITwoFAVerifier $verifier;
    public function __construct(string $method = 'email') {
        $this->method = $method;
    }

    public function useMethod(string|null $method) {
        $this->method = $method;
        if(empty($this->method)) {
            return $this->useVerifier(NoTwoFAVerifier::getInstance());
        }
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

    public function verify(LoginVerification $login_verification, string|null $token): bool {
        return $this->verifier->verify($login_verification, $token);
    }
}
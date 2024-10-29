<?php
namespace backend\utils\twofa;
use backend\models\LoginVerification;

interface ITwoFAVerifier {
    function verify(LoginVerification $login_verification, string|null $token): bool;
}
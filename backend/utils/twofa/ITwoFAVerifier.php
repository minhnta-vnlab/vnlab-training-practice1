<?php
namespace backend\utils\twofa;
use common\models\LoginVerification;

interface ITwoFAVerifier {
    function verify(LoginVerification $login_verification, string $token): bool;
}
<?php
namespace common\models;

use yii\base\Model;

/**
 * Update 2FA Form
 */
class UnlockUserForm extends Model {
    public $unlock_secret;
    public $password;

    public function rules() {
        return [
            [["unlock_secret", "password"], "required"],
            [["unlock_secret", "password"], "string"],
        ];
    }
}
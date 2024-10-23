<?php
namespace frontend\models;

use yii\base\Model;

/**
 * 2FA Verification Form
 */
class TwoFAForm extends Model {
    public $code;

    public function rules() {
        return [
            ["code", "required"],
            ["code", "string", "max" => 6],
        ];
    }
}
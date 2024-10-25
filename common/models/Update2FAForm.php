<?php
namespace common\models;

use Yii;
use yii\base\Model;

/**
 * Update 2FA Form
 */
class Update2FAForm extends Model {
    public $user_id;
    public $two_fa_method;
    public $code;

    public function rules() {
        return [
            [["user_id"], "required"],
            ["two_fa_method", "safe"],
            ["code", "string", "max" => 6],
        ];
    }
}
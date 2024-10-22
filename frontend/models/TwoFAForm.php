<?php
namespace frontend\models;

use Yii;
use yii\base\Model;
use common\models\User;

/**
 * Signup form
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
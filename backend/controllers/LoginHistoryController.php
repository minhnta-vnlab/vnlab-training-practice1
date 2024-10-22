<?php
    namespace backend\controllers;
    use yii\rest\ActiveController;

    class LoginHistoryController extends ActiveController {
        public $modelClass = "common\models\LoginHistory";
    }
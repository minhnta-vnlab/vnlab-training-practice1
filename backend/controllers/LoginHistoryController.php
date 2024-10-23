<?php
    namespace backend\controllers;
    use yii\rest\ActiveController;

    class LoginHistoryController extends ActiveController {
        public $modelClass = "common\models\LoginHistory";
        public function actions()
        {
            $actions = parent::actions();

            $actions['index']['dataFilter'] = [
                'class' => \yii\data\ActiveDataFilter::class,
                'searchModel' => $this->modelClass,
            ];

            return $actions;
        }
    }
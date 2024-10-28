<?php
    namespace backend\controllers;
    use common\models\LoginHistorySearch;
    use yii\rest\ActiveController;

    class LoginHistoryController extends ActiveController {
        public $modelClass = "common\models\LoginHistory";
        public $cacheKey;

        public function actions()
        {
            $actions = parent::actions();

            $actions['index']['dataFilter'] = [
                'class' => \yii\data\ActiveDataFilter::class,
                'searchModel' => LoginHistorySearch::class,
            ];

            return $actions;
        }

        public function behaviors() {
            $behaviors = parent::behaviors();
            $behaviors['cache'] = [
                'class'=> \backend\behaviors\CacheBehavior::class,
                'modelClass' => $this->modelClass
            ];
            $behaviors['cacheInvalidation'] = [
                'class'=> \backend\behaviors\CacheInvalidationBehavior::class,
                'modelClass' => $this->modelClass
            ];
            return $behaviors;
        }
    }
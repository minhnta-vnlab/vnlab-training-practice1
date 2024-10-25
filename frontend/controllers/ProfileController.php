<?php

namespace frontend\controllers;

use common\models\LoginHistory;
use common\models\Update2FAForm;
use Yii;
use yii\data\ArrayDataProvider;
use \yii\web\Controller;
use \yii\filters\AccessControl;

class ProfileController extends Controller {
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }
    public function actionIndex() {
        $user = Yii::$app->user->identity;
        $model = new Update2FAForm();
        /**
         * @var \yii\httpclient\Client
         */
        $client = Yii::$app->httpClient;

        $model->load(Yii::$app->request->post());

        if($model->validate() && $model->two_fa_method !== $user->two_fa_method) {
            $response = $client
                ->put("user/update-two-fa", $model->toArray())
                ->send();
            Yii::debug($response);
            if($response->statusCode == 200) {
                Yii::$app->session->setFlash("success","Updated 2FA method");
                Yii::$app->user->identity->two_fa_method = $model->two_fa_method;
            } else {
                Yii::debug($response);
                Yii::$app->session->setFlash("error", $response->data['message']);
            }
        } else {
            if(Yii::$app->request->isPost) {
                Yii::$app->session->setFlash('error', $model->getFirstErrors());
            }
        }

        $model->user_id = $user->id;
        $model->two_fa_method = $user->two_fa_method;

        $response = $client
            ->get("login-histories?filter[user_id]=".$model->user_id."&sort=-login_time")
            ->send();
        
        $dataProvider = new ArrayDataProvider([
            'allModels' => []
        ]);
        
        if($response->statusCode == 200) {
            $login_histories = array_map(function($a) {  
                $model = new LoginHistory();
                $model->attributes = $a;
                return $model;
            }, $response->data);

            $dataProvider = new ArrayDataProvider([
                'allModels' => $login_histories
            ]);
        }

        return $this->render("index", [
            'user' => $user,
            'model' => $model,
            'dataProvider' => $dataProvider
        ]);
    }
}
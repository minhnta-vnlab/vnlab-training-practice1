<?php

namespace frontend\controllers;

use common\models\Update2FAForm;
use Yii;
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

        if($model->load(Yii::$app->request->post()) && $model->validate() && $model->two_fa_method != $user->two_fa_method) {
            /**
             * @var \yii\httpclient\Client
             */
            $client = Yii::$app->httpClient;
            $response = $client
                ->put("user/update-two-fa", $model->toArray())
                ->send();
            if($response->statusCode == 200) {
                Yii::$app->session->setFlash("success","Updated 2FA method");
                Yii::$app->user->identity->two_fa_method = $model->two_fa_method;
            } else {
                Yii::debug($response);
                // Yii::$app->session->setFlash("error", $response->data);
            }
        }

        $model->user_id = $user->id;
        $model->two_fa_method = $user->two_fa_method;

        return $this->render("index", [
            'user' => $user,
            'model' => $model
        ]);
    }
}
<?php
namespace backend\controllers;

use common\models\LoginForm;
use common\models\LoginVerification;
use common\models\RegisterForm;
use yii\rest\Controller;
use Yii;
use yii\web\Response;

class AuthController extends Controller {
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        $behaviors['contentNegotiator'] = [
            'class' => \yii\filters\ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        return $behaviors;
    }

    public function actionRegister() {
        $model = new RegisterForm();
        $model->attributes = Yii::$app->request->post();
        
        if (!$model->validate()) {
            Yii::$app->response->statusCode = 400;
            return [
                'error' => 'Bad request',
                'message' => $model->getFirstErrors()
            ];
        }
        
        $user = $model->getUser();
        try {
            if($user->save()) {
                Yii::$app->response->statusCode = 200;
                return [
                    'message' => 'Register successfully',
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                        ]
                    ]
                ];
            }
            Yii::$app->response->statusCode = 500; // Internal Server Error
            return [
                'message'=> 'Register unsuccessfully',
                'data' => null
            ];
        } catch(\Exception $e) {
            Yii::error($e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 500; // Internal Server Error
            return [
                'message'=> 'An error occurred.',
                'error' => $e->getMessage(),
                'data'=> null
            ];
        }
    }

    public function actionLogin() {
        $model = new LoginForm();
        $model->attributes = Yii::$app->request->post();
        $user = $model->login();
        
        if($user) {
            Yii::$app->response->statusCode = 200;
            $login_verification = new LoginVerification();
            $time = time();
            $exp = $time + env('VERIFICATION_EXP') * 60;
            $login_verification->setAttributes([
                'user_id' => $user->id,
                'verification_method' => $user->two_fa_method,
                'issued_at' => $time,
                'expired_at' => $exp
            ]);
            // return [
            //     'message'=> 'Logged in successfully',
            //     'data' => [
            //         'user'=> [
            //             'id' => $user->id,
            //             'name' => $user->name,
            //             'email' => $user->email,
            //         ]
            //     ]
            // ];
        }

        Yii::$app->response->statusCode = 403; // Forbidden
        return [
            'message'=> 'Email or password is incorrect',
            'error' => 'Unauthorized',
            'data' => null
        ];
    }
}
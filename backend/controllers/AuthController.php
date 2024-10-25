<?php
namespace backend\controllers;

use common\models\LoginForm;
use common\models\LoginHistory;
use common\models\LoginVerification;
use common\models\RegisterForm;
use backend\utils\DateConverter;
use common\models\User;
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
                'data' => $user
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
            $login_verification = LoginVerification::find()->where(['user_id' => $user->id])->one();
            if($login_verification === null) {
                $login_verification = new LoginVerification();
            }
            $time = time();
            $exp = $time + env('VERIFICATION_EXP') * 60;
            $code = $user->two_fa_method == 'email' ? 
            Yii::$app->security->generateRandomString(6)
            : 
            null;
            $login_verification->setAttributes([
                'user_id' => $user->id,
                'verification_method' => $user->two_fa_method,
                'issued_at' => DateConverter::convertToSQL($time),
                'expired_at' => DateConverter::convertToSQL($exp),
                'code' => $code,
                'active' => 1,
                'num_try' => 0,
            ]);
            if(!$login_verification->save())  {
                Yii::$app->response->statusCode = 500;
                return [
                    'error' => 'Internal Server Error',
                    'message' => 'Something wrong when creating verification',
                    'data' => null
                ];
            }
            $login_verification->handle();
            Yii::$app->response->statusCode = 200;
            return [
                'message' => 'Successfully logged in by email and password, continue to verify the login.',
                'data' => [
                    'verification'=> [
                        'id' => $login_verification->id,
                        'verification_method' => $login_verification->verification_method,
                    ]
                ]
            ];
        }

        $user = User::find()->where(['email'=> $model->email])->one();

        if($user !== null) {
            $login_history = new LoginHistory();
            $login_history->user_id = $user->id;
            $login_history->message = "login_fail_wrong_password";
            $remoteIp = Yii::$app->request->headers->get('X-Real-IP');
            $login_history->ip = $remoteIp;
            $login_history->ua = Yii::$app->request->userAgent;
            $login_history->save();
        }

        Yii::$app->response->statusCode = 403; // Forbidden
        return [
            'message'=> 'Email or password is incorrect',
            'error' => 'Unauthorized',
            'data' => null
        ];
    }

    public function actionVerify() {
        $id = Yii::$app->request->get('id');
        $login_verification = LoginVerification::findOne($id);

        $ip = Yii::$app->request->userIP;
        $remoteIp = Yii::$app->request->headers->get('X-Real-IP');
        if ($remoteIp) {
            $ip = $remoteIp;
        }
        $ua = Yii::$app->request->userAgent;

        if(!$login_verification) {
            Yii::$app->response->statusCode = 404;
            return [
                'error' => 'Not Found',
                'message' => 'Login Verification with this id is not found',
            ];
        }

        $expired_at = strtotime($login_verification->expired_at);
        $user = $login_verification->user;
        $current_try = $login_verification->num_try + 1;

        if(time() > $expired_at || $login_verification->active == 0) {
            // Only log login_history when detect the login_verification->active is 1
            if($login_verification->active == 1) {
                $login_history = new LoginHistory();
                $login_history->user_id = $user->id;
                $login_history->message = "login_fail_verification_expired";
                $login_history->login_time = $login_verification->issued_at;
                $login_history->ip = $ip;
                $login_history->ua = $ua;
                $login_history->save();
            }

            $login_verification->active = 0;
            $login_verification->save();

            Yii::$app->response->statusCode = 400;
            return [
                'error' => 'Bad request',
                'message' => 'Login Verification already expired',
                'redirect' => 'login'
            ];
        }

        if($current_try > $login_verification->max_try) {
            $login_verification->active = 0;
            $login_verification->save();

            $login_history = new LoginHistory();
            $login_history->user_id = $user->id;
            $login_history->message = "login_fail_verification_max_try";
            $login_history->login_time = $login_verification->issued_at;
            $login_history->ip = $ip;
            $login_history->ua = $ua;
            $login_history->save();

            Yii::$app->response->statusCode = 400;
            return [
                'error' => 'Bad request',
                'message' => 'Reached the maximum number of verification attempts',
                'redirect' => 'login'
            ];
        }
        $login_verification->num_try = $current_try;
        $login_verification->save();

        $code = Yii::$app->request->post('code');

        /**
         * @var \backend\utils\twofa\TwoFAVerifier
         */
        $verifier = Yii::$app->twoFAVerifier;
        $result = $verifier
            ->useMethod($login_verification->verification_method)
            ->verify($login_verification, $code);

        $remain = $login_verification->max_try - $current_try;
        if(!$result) {
            Yii::$app->response->statusCode = 403;
            return [
                'error' => 'Unauthorized',
                'message' => "Verification code is not correct. You have ".$remain." attemps left.".
                "",
                'data' => [
                    'num_try' => $current_try,
                    'max_try' => $login_verification->max_try
                ]
            ];
        }

        $user = $login_verification->user;
        $login_verification->active = 0;
        $login_verification->save();
        
        $login_history = new LoginHistory();
        $login_history->user_id = $user->id;
        $login_history->message = "login_success";
        $login_history->ip = $ip;
        $login_history->ua = $ua;
        $login_history->save();

        return [
            'message' => 'Verified',
            'data' => [
                'user' => [
                    'id'=> $user->id,
                    'name' => $user->name,
                    'email'=> $user->email,
                ],
                'ip' => $ip,
                // 'proxy' => $proxyIp,
                'ua' => $ua,
                // 'debug-request' => Yii::$app->request,
                // 'debug-request-headers' => Yii::$app->request->headers,
                // 'debug-request-secure-headers' => Yii::$app->request->secureHeaders,
            ]
        ];
    }
}
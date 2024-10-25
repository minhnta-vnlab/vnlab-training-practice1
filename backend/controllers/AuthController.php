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

class AuthController extends Controller
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'contentNegotiator' => [
                'class' => \yii\filters\ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ]);
    }

    public function actionRegister()
    {
        $model = new RegisterForm();
        $model->attributes = Yii::$app->request->post();

        if (!$model->validate()) {
            return $this->respondWithError(400, 'Bad request', $model->getFirstErrors());
        }

        $user = $model->getUser();

        try {
            if ($user->save()) {
                return $this->respondWithSuccess(200, 'Register successfully', [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ]
                ]);
            }
            return $this->respondWithError(500, 'Register unsuccessfully', $user);
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return $this->respondWithError(500, 'An error occurred.', $e->getMessage());
        }
    }

    public function actionLogin()
    {
        $model = new LoginForm();
        $model->attributes = Yii::$app->request->post();
        $user = $model->login();

        if ($user) {
            return $this->handleSuccessfulLogin($user);
        }

        return $this->handleFailedLogin($model->email);
    }

    public function actionVerify()
    {
        $id = Yii::$app->request->get('id');
        $login_verification = LoginVerification::findOne($id);

        if (!$login_verification) {
            return $this->respondWithError(404, 'Not Found', 'Login Verification with this id is not found');
        }

        $ip = $this->getClientIp();
        $ua = Yii::$app->request->userAgent;

        if ($login_verification->isExpired()) {
            return $this->handleVerificationExpiration($login_verification, $ip, $ua);
        }

        if ($login_verification->hasExceedMaxTries()) {
            return $this->handleMaxTriesExceeded($login_verification, $ip, $ua);
        }

        return $this->processVerification($login_verification, $ip, $ua);
    }

    protected function respondWithError($statusCode, $message, $data = null)
    {
        Yii::$app->response->statusCode = $statusCode;
        return [
            'error' => $statusCode === 404 ? 'Not Found' : 'Bad request',
            'message' => $message,
            'data' => $data,
        ];
    }

    protected function respondWithSuccess($statusCode, $message, $data = [])
    {
        Yii::$app->response->statusCode = $statusCode;
        return [
            'message' => $message,
            'data' => $data,
        ];
    }

    protected function handleSuccessfulLogin($user)
    {
        $login_verification = LoginVerification::find()->where(['user_id' => $user->id])->one() ?? new LoginVerification();
        $time = time();
        $exp = $time + env('VERIFICATION_EXP') * 60;

        $login_verification->setAttributes([
            'user_id' => $user->id,
            'verification_method' => $user->two_fa_method,
            'issued_at' => DateConverter::convertToSQL($time),
            'expired_at' => DateConverter::convertToSQL($exp),
            'code' => $user->two_fa_method == 'email' ? Yii::$app->security->generateRandomString(6) : null,
            'active' => 1,
            'num_try' => 0,
        ]);

        if (!$login_verification->save()) {
            return $this->respondWithError(500, 'Internal Server Error', 'Something wrong when creating verification');
        }

        $login_verification->handle();
        return $this->respondWithSuccess(200, 'Successfully logged in by email and password, continue to verify the login.', [
            'verification' => [
                'id' => $login_verification->id,
                'verification_method' => $login_verification->verification_method,
            ]
        ]);
    }

    protected function handleFailedLogin($email)
    {
        $user = User::find()->where(['email' => $email])->one();
        if ($user !== null) {
            $this->logFailedLogin($user);
        }

        return $this->respondWithError(403, 'Email or password is incorrect', 'Unauthorized');
    }

    protected function logFailedLogin($user)
    {
        $login_history = new LoginHistory();
        $login_history->user_id = $user->id;
        $login_history->message = "login_fail_wrong_password";
        $remoteIp = Yii::$app->request->headers->get('X-Real-IP');
        $login_history->ip = $remoteIp;
        $login_history->ua = Yii::$app->request->userAgent;
        $login_history->save();
    }

    protected function getClientIp()
    {
        $remoteIp = Yii::$app->request->headers->get('X-Real-IP');
        return $remoteIp ?: Yii::$app->request->userIP;
    }

    protected function handleVerificationExpiration($login_verification, $ip, $ua)
    {
        $user = $login_verification->user;

        if ($login_verification->active == 1) {
            $this->logVerificationFailure($user, "login_fail_verification_expired", $login_verification->issued_at, $ip, $ua);
        }

        $login_verification->active = 0;
        $login_verification->save();

        return $this->respondWithError(400, 'Login Verification already expired', ['redirect' => 'login']);
    }


    protected function handleMaxTriesExceeded($login_verification, $ip, $ua)
    {
        $user = $login_verification->user;
        $login_verification->active = 0;
        $login_verification->save();

        $this->logVerificationFailure($user, "login_fail_verification_max_try", $login_verification->issued_at, $ip, $ua);

        return $this->respondWithError(400, 'Reached the maximum number of verification attempts', ['redirect' => 'login']);
    }

    protected function logVerificationFailure($user, $message, $issuedAt, $ip, $ua)
    {
        $login_history = new LoginHistory();
        $login_history->user_id = $user->id;
        $login_history->message = $message;
        $login_history->login_time = $issuedAt;
        $login_history->ip = $ip;
        $login_history->ua = $ua;
        $login_history->save();
    }

    protected function processVerification($login_verification, $ip, $ua)
    {
        $code = Yii::$app->request->post('code');
        /** @var \backend\utils\twofa\TwoFAVerifier $verifier */
        $verifier = Yii::$app->twoFAVerifier;
        $result = $verifier
            ->useMethod($login_verification->verification_method)
            ->verify($login_verification, $code);

        $current_try = $login_verification->num_try + 1;
        $remain = $login_verification->max_try - $current_try;

        if (!$result) {
            return $this->respondWithError(403, 'Verification code is not correct.', [
                'num_try' => $current_try,
                'max_try' => $login_verification->max_try,
                'message' => "You have $remain attempts left."
            ]);
        }

        $this->finalizeVerification($login_verification, $ip, $ua);
        return [
            'message' => 'Verified',
            'data' => [
                'user' => [
                    'id' => $login_verification->user->id,
                    'name' => $login_verification->user->name,
                    'email' => $login_verification->user->email,
                ],
                'ip' => $ip,
                'ua' => $ua,
            ]
        ];
    }

    protected function finalizeVerification($login_verification, $ip, $ua)
    {
        $user = $login_verification->user;
        $login_verification->active = 0;
        $login_verification->save();

        $login_history = new LoginHistory();
        $login_history->user_id = $user->id;
        $login_history->message = "login_success";
        $login_history->ip = $ip;
        $login_history->ua = $ua;
        $login_history->save();
    }
}
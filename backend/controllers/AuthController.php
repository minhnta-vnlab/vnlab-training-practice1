<?php
namespace backend\controllers;

use backend\services\LoginHistoryService;
use backend\services\LoginVerificationService;
use backend\services\UserLockService;
use backend\services\UserService;
use common\models\LoginForm;
use common\models\RegisterForm;
use common\models\UnlockUserForm;
use yii\rest\Controller;
use Yii;
use yii\web\Response;

class AuthController extends Controller
{
    protected LoginVerificationService $loginVerificationService;
    protected UserLockService $lockService;
    protected UserService $userService;
    protected LoginHistoryService $loginHistoryService;
    public function __construct(
        $id, 
        $module, 
        LoginVerificationService $loginVerificationService, 
        UserLockService $lockService,
        UserService $userService,
        LoginHistoryService $loginHistoryService,
        $config = []
    ) {
        $this->loginVerificationService = $loginVerificationService;
        $this->lockService = $lockService;
        $this->userService = $userService;
        $this->loginHistoryService = $loginHistoryService;
        parent::__construct($id, $module, $config);
    }
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

    // ACTIONS
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
        $login_verification = $this->loginVerificationService->getById($id);

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

    public function actionUnlock() {
        $model = new UnlockUserForm();
        $model->setAttributes(Yii::$app->request->post());
        if($model->validate()) {
            return $this->handleUnlock($model);
        }
        return $this->respondWithError(400, $model->getFirstErrors());
    }

    // RESPONSES

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

    // HANDLERS

    protected function handleSuccessfulLogin($user)
    {
        $login_verification = $this->loginVerificationService->createFromUser($user);

        if (!$login_verification) {
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

    protected function handleFailedLogin($email, $reason = 'Email or password is incorrect')
    {
        $lock = $this->lockService->getByUserEmail($email);
        if(isset($lock) && $lock->locked) {
            $this->logFailedLogin($lock->user, "login_fail_account_locked");
            $reason = "This account is locked";
            return $this->respondWithError(403, $reason, 'Unauthorized');
        }

        $user = $this->userService->getByEmail($email);
        if ($user !== null) {
            $this->logFailedLogin($user);
            $login_histories = $this->loginHistoryService->getRecentFailLoginHistories($user);
            if(count($login_histories) >= 5) {
                $this->lockUser($user, 'login_fail_spam');
                $reason = $reason.". Your account has been locked because failed too many times.";
            }
            return $this->respondWithError(403, $reason, 'Unauthorized');
        }

        return $this->respondWithError(403, "Unknown reason", 'Unauthorized');
    }

    protected function logFailedLogin($user, $reason = "login_fail_wrong_password")
    {
        $this->loginHistoryService->createWithMessage($user, $reason, Yii::$app->request);
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

        $this->loginVerificationService->deactivate($login_verification);

        return $this->respondWithError(400, 'Login Verification already expired', ['redirect' => 'login']);
    }


    protected function handleMaxTriesExceeded($login_verification, $ip, $ua)
    {
        $user = $login_verification->user;
        $this->loginVerificationService->deactivate($login_verification);

        $this->logVerificationFailure($user, "login_fail_verification_max_try", $login_verification->issued_at, $ip, $ua);
        $this->lockUser($user);

        return $this->respondWithError(400, 'Reached the maximum number of verification attempts.', ['redirect' => 'login', 'message' => " Your account has been locked."]);
    }

    protected function logVerificationFailure($user, $message, $issuedAt, $ip, $ua)
    {
        $this->loginHistoryService->createWithMessage($user, $message, Yii::$app->request);
    }

    protected function processVerification($login_verification, $ip, $ua)
    {
        $code = Yii::$app->request->post('code');
        $verifier = Yii::$app->twoFAVerifier;
        $result = $verifier
            ->useMethod($login_verification->verification_method)
            ->verify($login_verification, $code);

        $current_try = $login_verification->num_try + 1;
        $remain = $login_verification->max_try - $current_try;

        if (!$result) {
            $login_verification->num_try = $current_try;
            $login_verification->save();
            if($login_verification->hasExceedMaxTries()) {
                return $this->handleMaxTriesExceeded($login_verification, $ip, $ua);
            }
            return $this->respondWithError(403, 'Verification code is not correct.', [
                'num_try' => $current_try,
                'max_try' => $login_verification->max_try,
                'message' => "You have $remain attempts left."
            ]);
        }

        $this->loginVerificationService->deactivate($login_verification);
        $this->loginHistoryService->createSuccess($login_verification->user, Yii::$app->request);
        return [
            'message' => 'Verified',
            'data' => [
                'user' => [
                    'id' => $login_verification->user->id,
                    'name' => $login_verification->user->name,
                    'email' => $login_verification->user->email,
                    'phone_number' => $login_verification->user->phone_number,
                    'two_fa_method' => $login_verification->user->two_fa_method,
                    'two_fa_secret' => $login_verification->user->two_fa_secret,
                ],
                'ip' => $ip,
                'ua' => $ua,
            ]
        ];
    }

    protected function handleUnlock(UnlockUserForm $model) {
        $secret = $model->unlock_secret;
        $lock = $this->lockService->getBySecret($secret);
        if ($lock) {
            if($this->lockService->unlock($lock, $model->password)) {
                return $this->respondWithSuccess(200, "Account unlocked");
            }
            return $this->respondWithError(400, "Wrong password");
        }
        return $this->respondWithError(400, "No lock is found");
    }

    protected function lockUser($user, $reason = "max_try_exceed") {
        $lock = $this->lockService->createLockByUser($user, $reason);
        if($lock) {
            $host = Yii::$app->request->hostInfo;
            Yii::$app->mailer->compose()
                ->setTo($user->email)
                ->setFrom("website@example.com")
                ->setSubject("Account locked")
                ->setTextBody("Your account has been locked due to". 
                                    $reason 
                                    .". To unlock your account please visit: $host/site/unlock?secret=".$lock->unlock_secret)
                ->send();
            return true;
        }
        return false;
    }
}
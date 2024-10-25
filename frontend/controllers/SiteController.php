<?php
namespace frontend\controllers;

use common\models\User;
use frontend\models\TwoFAForm;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use common\models\RegisterForm;
use frontend\services\AuthService;

/**
 * Site controller
 */
class SiteController extends Controller
{
    protected $authService;

    public function __construct($id, $module, AuthService $authService, $config = [])
    {
        $this->authService = $authService;
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionLogin()
    {
        $model = $this->authService->handleLogin();
        if ($model instanceof LoginForm) {
            return $this->render('login', ['model' => $model]);
        }
        return $model; // Redirect or response based on successful login
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    public function actionSignup()
    {
        $model = $this->authService->handleSignup();
        if ($model instanceof RegisterForm) {
            return $this->render('signup', ['model' => $model]);
        }
        return $model; // Redirect or response based on successful signup
    }

    public function actionVerifyLogin()
    {
        $model = new TwoFAForm();
        $result = $this->authService->handleVerifyLogin($model);

        if (isset($result['redirect'])) {
            return $this->redirect($result['redirect']);
        }

        return $this->render('loginVerification', [
            'method' => $result['method'],
            'email' => $result['email'],
            'model' => $model,
        ]);
    }
}
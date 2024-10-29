<?php
namespace frontend\controllers;

use common\models\UnlockUserForm;
use frontend\models\User;
use frontend\consts\TagKey;
use frontend\models\TwoFAForm;
use frontend\consts\CacheKey;
use frontend\services\AuthService;
use Yii;
use yii\caching\TagDependency;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use common\models\RegisterForm;

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
            [
                'class' => 'yii\filters\PageCache',
                'only' => ['index'],
                'duration' => 60,
                'variations' => [
                    Yii::$app->language,
                ],
                'dependency' => new TagDependency(['tags' => TagKey::USER])
            ],
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
        TagDependency::invalidate(Yii::$app->cache, TagKey::USER->name);
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

        $method = Yii::$app->request->get("method");
        $email = Yii::$app->request->get("email");

        return $this->render('loginVerification', [
            'method' => $method,
            'email' => $email,
            'model' => $model,
        ]);
    }

    public function actionUnlock() {
        $model = new UnlockUserForm();
        if($model->load(Yii::$app->request->post()) && $model->validate()) {
            $result = $this->authService->handleUnlock($model);
            if (isset($result['redirect'])) {
                return $this->redirect($result['redirect']);
            }
        }
        $secret = Yii::$app->request->get('secret');
        $model->unlock_secret = $secret;
        $model->password = '';
        return $this->render('unlock', [
            'model' => $model,
        ]);
    }
 }
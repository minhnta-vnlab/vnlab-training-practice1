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

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post())) {
            /**
             * @var \yii\httpclient\Client
             */
            $httpClient = Yii::$app->httpClient;
            $response = $httpClient
                ->post('auth/login', $model->toArray())
                ->send();
            
            if($response->statusCode == 200) {
                $id = $response->data["data"]["verification"]["id"];
                $method = $response->data["data"]["verification"]["verification_method"];
                $email = $model->email;
                return $this->redirect("/site/verify-login?id=$id&method=$method&email=$email");
            } else {
                if(isset($response->data["message"])) {
                    Yii::$app->session->setFlash("error", $response->data["message"]);
                }
            }
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionSignup()
    {
        $model = new RegisterForm();

        if ($model->load(Yii::$app->request->post())) {
            /**
             *
             * @var \yii\httpclient\Client
             */
            $httpClient = Yii::$app->httpClient;
            $response = $httpClient
                ->post("auth/register", $model->toArray())
                ->send();
            if ($response->getStatusCode() == 200) {
                Yii::$app->session->setFlash("success","Thank you for registration. Please login into your account");
                $this->redirect("/site/login");
            } else {
                Yii::$app->session->setFlash("error", $response->data["message"]);
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    public function actionVerifyLogin() {
        $id = Yii::$app->request->get("id");
        $method = Yii::$app->request->get("method");
        $email = Yii::$app->request->get("email");
        $model = new TwoFAForm();

        if(empty($method) || $model->load(Yii::$app->request->post())) {
            /** @var \yii\httpclient\Client */
            $httpClient = Yii::$app->httpClient;
            $response = $httpClient
                ->post("auth/verify?id=$id", $model->toArray())
                ->setHeaders([
                    "X-Forwarded-For" => Yii::$app->request->userIP,
                    "User-Agent" => Yii::$app->request->userAgent
                ])
                ->send();
            if ($response->getStatusCode() == 200) {
                Yii::$app->session->setFlash("success","Login Successfully");

                $user = new User();
                $user->setAttributes(
                    $response->data["data"]["user"]
                );
                $user->id = $response->data["data"]["user"]["id"];

                if(!Yii::$app->user->login($user, 3600 * 24 * 30)) {
                    Yii::$app->session->setFlash("error",Yii::$app->user);
                }

                return $this->goHome();
            } else {
                Yii::$app->session->setFlash("error",$response->data["message"]);
            }
        }

        $model->code = '';

        return $this->render('loginVerification', [
            'method' => $method,
            'email'=> $email,
            'model'=> $model,
        ]);
    }
}
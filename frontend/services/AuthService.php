<?php
namespace frontend\services;

use common\models\User;
use common\models\LoginForm;
use common\models\RegisterForm;
use frontend\models\TwoFAForm;
use Yii;

class AuthService
{
    public function handleLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return Yii::$app->controller->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post())) {
            $httpClient = Yii::$app->httpClient;
            $response = $httpClient
                ->post('auth/login', $model->toArray())
                ->setHeaders([
                    "X-Forwarded-For" => Yii::$app->request->userIP,
                    "User-Agent" => Yii::$app->request->userAgent,
                ])
                ->send();

            if ($response->statusCode == 200) {
                $id = $response->data["data"]["verification"]["id"];
                $method = $response->data["data"]["verification"]["verification_method"];
                return Yii::$app->controller->redirect("/site/verify-login?id=$id&method=$method&email={$model->email}");
            } else {
                Yii::$app->session->setFlash("error", $response->data["message"] ?? 'Login failed.');
            }
        }

        $model->password = '';
        return $model;
    }

    public function handleSignup()
    {
        $model = new RegisterForm();
        if ($model->load(Yii::$app->request->post())) {
            $httpClient = Yii::$app->httpClient;
            $response = $httpClient->post("auth/register", $model->toArray())->send();

            if ($response->getStatusCode() == 200) {
                Yii::$app->session->setFlash("success", "Thank you for registration. Please login into your account");
                return Yii::$app->controller->redirect("/site/login");
            } else {
                Yii::$app->session->setFlash("error", $response->data["message"]);
            }
        }
        return $model;
    }

    public function handleVerifyLogin(TwoFAForm $model)
    {
        $id = Yii::$app->request->get("id");
        $method = Yii::$app->request->get("method");
        $email = Yii::$app->request->get("email");

        if (empty($method) || $model->load(Yii::$app->request->post())) {
            $httpClient = Yii::$app->httpClient;
            $response = $httpClient
                ->post("auth/verify?id=$id", $model->toArray())
                ->setHeaders([
                    "X-Forwarded-For" => Yii::$app->request->userIP,
                    "User-Agent" => Yii::$app->request->userAgent,
                ])
                ->send();

            if ($response->getStatusCode() == 200) {
                Yii::$app->session->setFlash("success", "Login Successfully");

                $user = new User();
                $user->setAttributes($response->data["data"]["user"]);
                if (!Yii::$app->user->login($user, 3600 * 24 * 30)) {
                    Yii::$app->session->setFlash("error", "Login failed.");
                }

                return ['redirect' => '/site/index'];
            } else {
                Yii::$app->session->setFlash("error", $response->data["message"]);
                return ['redirect' => $response->data['data']['redirect'] ?? null];
            }
        }

        return ['method' => $method, 'email' => $email, 'redirect' => null];
    }
}
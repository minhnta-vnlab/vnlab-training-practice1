<?php
namespace frontend\services;

use common\models\LoginForm;
use common\models\RegisterForm;
use common\models\UnlockUserForm;
use frontend\models\User;
use frontend\consts\TagKey;
use frontend\models\TwoFAForm;
use Yii;
use yii\caching\TagDependency;

class AuthService
{
    public function handleLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return Yii::$app->controller->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post())) {
            return $this->processLogin($model);
        }

        $model->password = '';
        return $model;
    }

    private function processLogin(LoginForm $model)
    {
        $httpClient = Yii::$app->httpClient;
        $response = $httpClient
            ->post('auth/login', $model->toArray())
            ->setHeaders($this->getRequestHeaders())
            ->send();

        if ($response->statusCode == 200) {
            return $this->redirectToVerification($response, $model);
        } else {
            Yii::$app->session->setFlash("error", $response->data["message"] ?? 'Login failed.');
        }

        return $model;
    }

    private function redirectToVerification($response, LoginForm $model)
    {
        $id = $response->data["data"]["verification"]["id"];
        $method = $response->data["data"]["verification"]["verification_method"];
        return Yii::$app->controller->redirect("/site/verify-login?id=$id&method=$method&email={$model->email}");
    }

    public function handleSignup()
    {
        $model = new RegisterForm();
        if ($model->load(Yii::$app->request->post())) {
            return $this->processSignup($model);
        }
        return $model;
    }

    private function processSignup(RegisterForm $model)
    {
        $httpClient = Yii::$app->httpClient;
        $response = $httpClient->post("auth/register", $model->toArray())->send();

        if ($response->getStatusCode() == 200) {
            Yii::$app->session->setFlash("success", "Thank you for registration. Please login into your account");
            return Yii::$app->controller->redirect("/site/login");
        } else {
            Yii::$app->session->setFlash("error", $response->data["message"]);
        }

        return $model;
    }

    public function handleVerifyLogin(TwoFAForm $model)
    {
        $id = Yii::$app->request->get("id");
        $method = Yii::$app->request->get("method");
        $email = Yii::$app->request->get("email");

        if (empty($method) || $model->load(Yii::$app->request->post())) {
            return $this->processVerification($model, $id);
        }

        return ['method' => $method, 'email' => $email, 'redirect' => null];
    }

    private function processVerification(TwoFAForm $model, $id)
    {
        $httpClient = Yii::$app->httpClient;
        $response = $httpClient
            ->post("auth/verify?id=$id", $model->toArray())
            ->setHeaders($this->getRequestHeaders())
            ->send();

        if ($response->getStatusCode() == 200) {
            return $this->loginUser($response);
        } else {
            $this->handleVerificationError($response);
            return ['redirect' => $response->data['data']['redirect'] ?? null];
        }
    }

    private function loginUser($response)
    {
        Yii::$app->session->setFlash("success", "Login Successfully");

        $user = new User();
        $user->setAttributes($response->data["data"]["user"]);
        if (!Yii::$app->user->login($user, 3600 * 24 * 30)) {
            Yii::$app->session->setFlash("error", "Login failed.");
        }
        TagDependency::invalidate(Yii::$app->cache, TagKey::USER->name);

        return ['redirect' => '/site/index'];
    }

    private function handleVerificationError($response)
    {
        if (isset($response->data["message"]) && isset($response->data["data"]["message"])) {
            Yii::$app->session->setFlash("error", $response->data["message"] . " " . $response->data["data"]["message"]);
        } elseif (isset($response->data['message'])) {
            Yii::$app->session->setFlash("error", $response->data['message']);
        } elseif (isset($response->data["data"]["message"])) {
            Yii::$app->session->setFlash("error", $response->data['data']['message']);
        }
    }

    public function handleUnlock(UnlockUserForm $model) {
        $response = Yii::$app->httpClient
            ->post("/auth/unlock", $model->toArray())
            ->send();
        if($response->getIsOk()) {
            Yii::$app->session->setFlash("success","Account unlocked. Please login to your account.");
            return ['redirect' => '/site/login'];
        }
        Yii::$app->session->setFlash('error',$response->data['message']);
    }

    private function getRequestHeaders()
    {
        return [
            "X-Forwarded-For" => Yii::$app->request->userIP,
            "User-Agent" => Yii::$app->request->userAgent,
        ];
    }
}
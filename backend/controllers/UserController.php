<?php
namespace backend\controllers;

use yii\rest\ActiveController;
use common\models\Update2FAForm;
use Yii;
use yii\web\Response;
use backend\models\User;
use backend\services\UserService;

class UserController extends ActiveController
{
    public $modelClass = "common\models\User";
    public $cacheKey;
    protected $userService;

    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['cache'] = [
            'class'=> \backend\behaviors\CacheBehavior::class,
            'modelClass' => $this->modelClass
        ];
        $behaviors['cacheInvalidation'] = [
            'class'=> \backend\behaviors\CacheInvalidationBehavior::class,
            'modelClass' => $this->modelClass
        ];
        return $behaviors;
    }

    public function __construct($id, $module, UserService $userService, $config = [])
    {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    public function actionTwoFactorQr()
    {
        $id = Yii::$app->request->get("id");
        $user = User::findOne($id);
        if (!$user) {
            return $this->handleResponse(404, 'User not found');
        }

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'image/svg+xml');

        return $this->userService->generateTwoFactorQr($user);
    }

    public function actionUpdateTwoFa()
    {
        $model = new Update2FAForm();
        $model->attributes = Yii::$app->request->bodyParams;

        if (!$model->validate()) {
            return $this->handleResponse(400, 'Bad request', $model->getFirstErrors());
        }

        $updateResult = $this->userService->updateTwoFa($model);
        if (isset($updateResult['error'])) {
            return $this->handleResponse($updateResult['status'], $updateResult['error']);
        }

        return [
            'message' => $updateResult['message'],
            'data' => $updateResult['data'],
        ];
    }

    protected function handleResponse($statusCode, $message, $errors = null)
    {
        Yii::$app->response->statusCode = $statusCode;
        return [
            'error' => $message,
            'message' => $errors ?? null,
        ];
    }
}
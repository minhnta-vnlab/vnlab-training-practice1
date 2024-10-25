<?php
namespace frontend\controllers;

use common\models\LoginHistory;
use common\models\Update2FAForm;
use Yii;
use yii\data\ArrayDataProvider;
use yii\web\Controller;
use yii\filters\AccessControl;
use frontend\services\TwoFAService;
use frontend\repositories\LoginHistoryRepository;

class ProfileController extends Controller
{
    private $twoFAService;
    private $loginHistoryRepository;

    public function __construct($id, $module, TwoFAService $twoFAService, LoginHistoryRepository $loginHistoryRepository, $config = [])
    {
        $this->twoFAService = $twoFAService;
        $this->loginHistoryRepository = $loginHistoryRepository;
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $model = new Update2FAForm();
        
        $model->load(Yii::$app->request->post());

        if ($this->twoFAService->updateTwoFA($model, $user)) {
            Yii::$app->session->setFlash("success", "Updated 2FA method");
        } else {
            $this->handleError($model);
        }

        $model->user_id = $user->id;
        $model->two_fa_method = $user->two_fa_method;

        $dataProvider = $this->loginHistoryRepository->getLoginHistories($model->user_id);

        return $this->render("index", [
            'user' => $user,
            'model' => $model,
            'dataProvider' => $dataProvider
        ]);
    }

    private function handleError($model)
    {
        if (Yii::$app->request->isPost) {
            Yii::$app->session->setFlash('error', $model->getFirstErrors());
        }
    }
}
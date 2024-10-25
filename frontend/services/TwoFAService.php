<?php
namespace frontend\services;

use Yii;
use common\models\Update2FAForm;

class TwoFAService
{
    public function updateTwoFA(Update2FAForm $model, $user)
    {
        if ($model->validate() && $model->two_fa_method !== $user->two_fa_method) {
            $client = Yii::$app->httpClient;
            $response = $client->put("user/update-two-fa", $model->toArray())->send();

            if ($response->statusCode == 200) {
                $user->two_fa_method = $model->two_fa_method;
                return true;
            } else {
                Yii::debug($response);
                Yii::$app->session->setFlash("error", $response->data['message']);
            }
        }
        return false;
    }
}

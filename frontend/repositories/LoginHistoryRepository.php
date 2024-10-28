<?php
namespace frontend\repositories;

use common\models\LoginHistory;
use frontend\cache\dependencies\ApiDependency;
use frontend\consts\CacheKey;
use frontend\consts\TagKey;
use Yii;
use yii\caching\ChainedDependency;
use yii\caching\TagDependency;
use yii\data\ArrayDataProvider;

class LoginHistoryRepository
{
    public function getLoginHistories($userId)
    {
        $loginHistories = Yii::$app->cache->getOrSet(CacheKey::USER_LOGIN_HISTORIES->name, function () use ($userId) {
            
            TagDependency::invalidate(Yii::$app->cache, TagKey::USER_LOGIN_HISTORIES->name);
            
            $client = Yii::$app->httpClient;
            $response = $client->get("login-histories?filter[user_id]=$userId&sort=-login_time")->send();
    
            if ($response->statusCode == 200) {
                $loginHistories = array_map(function ($data) {
                    $model = new LoginHistory();
                    $model->setAttributes($data);
                    return $model;
                }, $response->data);

                return $loginHistories;
            }

            return [];
        }, null, new ChainedDependency([
            'dependencies' => [
                new TagDependency(['tags' => TagKey::USER]),
                new ApiDependency([
                    'apiUrl' => "login-histories?per-page=1&filter[user_id]=$userId&sort=-login_time"
                ])
            ]
        ]));

        if (count($loginHistories) == 0) {
            Yii::$app->cache->delete(CacheKey::USER_LOGIN_HISTORIES->name);
        }

        return new ArrayDataProvider(['allModels' => $loginHistories]);
    }
}

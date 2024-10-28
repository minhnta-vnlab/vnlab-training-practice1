<?php
namespace frontend\cache\dependencies;

use yii\caching\Dependency;
use Yii;

class ApiDependency extends Dependency {
    public string $apiUrl;
    public string $method = "GET";
    protected function generateDependencyData($cache) {
        $data = Yii::$app->cache->get($this->apiUrl);
        return $data;
    }

    public function isChanged($cache) {
        $data = $cache->get($this->apiUrl);
        $response = Yii::$app->httpClient
            ->createRequest()
            ->setUrl($this->apiUrl)
            ->setMethod($this->method)
            ->send();
        if($response->getIsOk()) {
            return $data == $response->getData();
        } else {
            Yii::error($response->getData());
        }
        return true;
    }
}
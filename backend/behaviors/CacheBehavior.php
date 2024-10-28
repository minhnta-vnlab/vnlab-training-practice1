<?php
namespace backend\behaviors;

use yii\base\Behavior;
use yii\base\Controller;
use Yii;

class CacheBehavior extends Behavior {
    public $modelClass;
    public $cacheDuration = null;
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
            Controller::EVENT_AFTER_ACTION => 'afterAction',
        ];
    }
    /**
     * @param \yii\base\ActionEvent $event
     */

    public function beforeAction($event)
    {
        // Generate a cache key based on the request parameters
        $cacheKey = $this->generateCacheKey($event->action->id);
        $cache = Yii::$app->cache;
        // Try to get cached data
        $data = $cache->get($cacheKey);
        // $data = false;
        if ($data !== false) {
            // If data exists in cache, send it as a response
            $data['cache_key'] = $cacheKey;
            $event->sender->response->format = \yii\web\Response::FORMAT_JSON;
            $event->sender->response->data = $data;
            $event->isValid = false; // Prevent the action from being executed
        } else {
            // Save the cache key for later use in afterAction
            $event->sender->cacheKey = $cacheKey;
        }
    }

    public function afterAction($event)
    {
        // Cache the response data after the action is executed
        if (isset($event->sender->cacheKey)) {
            $cache = Yii::$app->cache;
            $cacheKey = $event->sender->cacheKey;
            $data = $event->sender->response->data;

            // Store the data in cache
            if(isset($data)) {
                $cache->set($cacheKey, $data, $this->cacheDuration);
            }
        }
    }

    protected function generateCacheKey($action)
    {
        // Generate a unique key based on the request URL and parameters
        return 'api_cache_'.($this->modelClass).$action;
    }
}
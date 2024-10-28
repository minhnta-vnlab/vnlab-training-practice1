<?php
namespace backend\behaviors;

use yii\base\Behavior;
use yii\base\Controller;
use Yii;

class CacheInvalidationBehavior extends Behavior
{
    public $modelClass;
    public function events()
    {
        return [
            Controller::EVENT_AFTER_ACTION => 'invalidateCache',
        ];
    }

    public function invalidateCache($event)
    {
        $action = $event->action->id;

        // Check if the action is a CUD operation
        if (in_array($action, ['create', 'update', 'delete'])) {
            $cacheKey = $this->generateCacheKey();
            foreach (['create', 'update', 'delete'] as $action) {
                $this->cache->delete($cacheKey.$action);
            }
            
            // Optionally, invalidate related cache keys
            // Yii::$app->cache->delete('related_cache_key');
        }
    }

    protected function generateCacheKey()
    {
        // Generate a unique key based on the request URL and parameters
        return 'api_cache_'.($this->modelClass);
    }
}
<?php

namespace App\Helpers;

class LoggerBuilder implements LoggerInterface
{
    /**
     * @param $description
     * @param $model
     * @param $logType 
     * @param $withProperties 
     * 
     * @return void
     */
    public function createLog($description, $model, $logType = 'default', $withProperties = [] )
    {
         activity($logType)
            ->performedOn($model)
            ->withProperties($withProperties)
            ->log($description);
    }
}

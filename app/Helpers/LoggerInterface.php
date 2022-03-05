<?php

namespace App\Helpers;

Interface LoggerInterface 
{
    public function createLog($description, $model, $logType, $withProperties );
}

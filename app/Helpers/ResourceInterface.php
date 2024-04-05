<?php

namespace App\Helpers;

Interface ResourceInterface
{
    public function jsonResponse($modelType, $data);
}

<?php

namespace App\Helpers;

Interface ResponseInterface
{
    public function apiJsonResponse($code, $message, $data, $error);
}

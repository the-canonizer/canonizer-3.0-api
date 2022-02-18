<?php

namespace App\Http\Controllers;


use App\Helpers\ResponseInterface;
use App\Models\Namespaces;


class NicknameController extends Controller
{

    public function __construct(ResponseInterface $resProvider)
    {
        $this->resProvider = $resProvider;
    }
   

    
}

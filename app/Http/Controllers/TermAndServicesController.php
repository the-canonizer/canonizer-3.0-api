<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TermAndServices;

class TermAndServicesController extends Controller
{
    //
    public function getTermAndServicesContent()
    {
        try {
            $termAndServices = TermAndServices::all();
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $termAndServices, '');
        } catch (\Throwable $e) {
            
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
       
    }
}

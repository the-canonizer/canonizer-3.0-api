<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PrivacyPolicy;


class PrivacyPolicyController extends Controller
{
    //
    public function getPrivacyPolicyContent()
    {
        try {
            $privacyPolicy = PrivacyPolicy::all();
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $privacyPolicy, '');
        } catch (\Throwable $e) {
            
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
       
    }
}

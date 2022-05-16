<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ActivityLogger;

class ActivityController extends Controller
{
    public function getAds(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getAdsValidationRules(), $this->validationMessages->getAdsValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $pageName = $request->page_name;
        $ads = [];

        try {
            $page = Page::where('name', $pageName)->first();
            if ($page && $page->has('ads')) {
                $indexs = ['client_id', 'slot', 'format', 'adtest', 'is_responsive', 'status'];
                $ads = $this->resourceProvider->jsonResponse($indexs, $page->ads);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $ads, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

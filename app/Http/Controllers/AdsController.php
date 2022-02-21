<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdsRequest;
use App\Http\Resources\AdsResource;
use App\Models\Page;
use Illuminate\Http\Request;

class AdsController extends Controller
{
    /**
     * Get ads list filtered by page 
     * 
     * @param AdRequest $request
     * @param Page $pagName
     * @return Response
     */
    public function pageAdsListing(AdsRequest $request)
    {
        $pageName = $request->page_name;
        $ads = [];
        
        try {
            $page = Page::where('name', $pageName)->first();
            if($page) {
                $ads = AdsResource::collection($page->ads);
            }
            return $this->resProvider->apiJsonResponse(200, config('message.success.success'), $ads, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, config('message.error.exception'), '', $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Request\AdsRequest;
use App\Http\Resources\AdsResource;
use App\Models\Page;

class AdsController extends Controller
{
    /**
     * @OA\Post(path="/ads",
     *   tags={"ads"},
     *   summary="get ads list",
     *   description="This is used to get the ads list for specific page.",
     *   operationId="ad",
     *   @OA\Parameter(
     *     name="page_name",
     *     required=true,
     *     in="query",
     *     description="The page name is required",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     *   @OA\Response(resppageImagesListingonse=400, description="Somethig went wrong")
     * )
    */
    public function getAds(AdsRequest $request)
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

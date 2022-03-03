<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Http\Request\AdsRequest;
use App\Helpers\ResponseInterface;
use App\Helpers\ResourceInterface;

class AdsController extends Controller
{

    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider)
    {
        $this->resourceProvider = $resProvider;
        $this->responseProvider = $respProvider;
    }

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

        try{
            $page = Page::where('name', $pageName)->first();
            if($page && $page->has('ads')) {
                $ads = $this->resourceProvider->jsonResponse('ad', $page->ads);
            }
            return $this->responseProvider->apiJsonResponse(200, config('message.success.success'), $ads, '');
        }catch(Exception $e){
            return $this->responseProvider->apiJsonResponse(400, config('message.error.exception'), $e->getMessage(), '');
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Request\ImageRequest;
use App\Http\Resources\ImageResource;
use App\Models\Page;

class ImageController extends Controller
{
    /**
     * @OA\Post(path="/images",
     *   tags={"images"},
     *   summary="get images list",
     *   description="This is used to get the images list for specific page.",
     *   operationId="image",
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
    public function getImages(ImageRequest $request) 
    {
        $pageName = $request->page_name;
        $images = [];

        try {
            $page = Page::where('name', $pageName)->first();
            if($page) {
                $images = ImageResource::collection($page->images);
            }
            return $this->resProvider->apiJsonResponse(200, config('message.success.success'), $images, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, config('message.error.exception'), '', $e->getMessage());
        }
    }
}

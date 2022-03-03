<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Http\Request\ImageRequest;
use App\Helpers\ResponseInterface;
use App\Helpers\ResourceInterface;

class ImageController extends Controller
{
    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider)
    {
        $this->resourceProvider = $resProvider;
        $this->responseProvider = $respProvider;
    }

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
            if($page && $page->has('images')) {
                $images = $this->resourceProvider->jsonResponse('image', $page->images);
            }
            return $this->responseProvider->apiJsonResponse(200, config('message.success.success'), $images, '');
        } catch (\Throwable $e) {
            return $this->responseProvider->apiJsonResponse(400, config('message.error.exception'), '', $e->getMessage());
        }
    }
}

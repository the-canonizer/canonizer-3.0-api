<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Http\Resources\ErrorResource;

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
    public function getImages(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getImageValidationRules(), $this->validationMessages->getImageValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $pageName = $request->page_name;
        $images = [];
        try {
            $page = Page::where('name', $pageName)->first();
            if ($page && $page->has('images')) {
                $images = $this->resourceProvider->jsonResponse('image', $page->images);
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $images, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

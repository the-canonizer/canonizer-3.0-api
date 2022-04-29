<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Page;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Http\Resources\ErrorResource;
use App\Helpers\ResponseInterface;
use App\Helpers\ResourceInterface;
use App\Http\Request\ValidationRules;
use App\Http\Request\ValidationMessages;
class AdsController extends Controller
{

    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider, ValidationRules $rules, ValidationMessages $validationMessages)
    {
        $this->rules = $rules;
        $this->validationMessages = $validationMessages;
        $this->resourceProvider  = $resProvider;
        $this->resProvider = $respProvider;
    }

    /**
     * @OA\Post(path="/ads",
     *   tags={"Ads"},
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
     * )
     */
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

<?php

namespace App\Http\Controllers;

use App\Models\MetaTag;
use Illuminate\Http\Request;
use App\Helpers\ResponseInterface;
use App\Http\Resources\ErrorResource;
use App\Http\Request\Validate;

class MetaTagController extends Controller
{
    public function __construct(ResponseInterface $respProvider)
    {
        $this->resProvider = $respProvider;
    }

    /**
     * @OA\Get(path="/meta-tags",
     *   tags={"meta-tags"},
     *   summary="",
     *   description="Get list of meta-tags",
     *   operationId="meta-tags",
     *   @OA\Response(response=200, description="Sucsess")
     *   @OA\Response(response=400, description="Something went wrog")
     * )
     */
    public function getMetaTags(Request $request)
    {
        try {
            $page_name = $request->post('page_name') ?? NULL;

            if ($page_name !== NULL) {
                $metaTag = MetaTag::select('id', 'title', 'description', 'route', 'image_url', 'keywords')->where('page_name', strtolower($page_name))->first();
                if ($metaTag === NULL) {
                    $metaTag = MetaTag::where('page_name', 'default')->first();
                }

                $metaTag->keywords = implode('|', (array)$metaTag->keywords);
            } else {
                $metaTag = MetaTag::all();
                $metaTag = $metaTag->keyBy('page_name')->map(function ($tag) {
                    $tag->keywords = implode('|', (array)$tag->keywords);
                    return $tag->only(['id', 'title', 'description', 'route', 'image_url', 'keywords']);
                })->all();
            }

            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'),  $metaTag, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

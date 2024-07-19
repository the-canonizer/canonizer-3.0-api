<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResponseInterface;
use App\Http\Resources\ErrorResource;
use App\Http\Request\Validate;
use App\Models\Category;
use App\Models\Video;

class VideoController extends Controller
{
    public function __construct(ResponseInterface $respProvider)
    {
        $this->resProvider = $respProvider;
    }

    /**
     * @OA\Get(path="/videos",
     *   tags={"videos"},
     *   summary="",
     *   description="Get list of videos",
     *   operationId="videos",
     *   @OA\Response(response=200, description="Sucsess")
     *   @OA\Response(response=400, description="Something went wrog")
     * )
     */
    public function getVideos(Request $request)
    {
        try {

            $categories = Category::with(['videos:id,title,thumbnail'])->get();

            return $this->resProvider->apiJsonResponse(!count($categories) ? 404 : 200, trans('message.success.success'),  $categories, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(500, trans('message.error.exception'), '', $e->getMessage());
        }
    }

        /**
     * @OA\Get(path="/videos/{category}/{categoryId}",
     *   tags={"getVideosByCategory"},
     *   summary="",
     *   description="Get list of videos by category",
     *   operationId="getVideosByCategory",
     *   @OA\Response(response=200, description="Sucsess")
     *   @OA\Response(response=400, description="Something went wrog")
     * )
     */
    public function getVideosByCategory($category, $categoryId)
    {
        try {
            $categories = Category::with(['videos.resolutions'])->where('id', $categoryId)->get();
            
            $categories = collect($categories)->map(function ($category) {
                $category->videos = collect($category->videos)->map(function ($video) {
                    $video->resolutions = collect($video->resolutions)->map(function ($resolution) use ($video) {
                        $resolution->link = $video->link . '_' . $resolution->resolution . '.' . $video->extension;
                        unset($resolution->resolution);
                        return $resolution;
                    });
                    unset($video->link, $video->extension, $video->videos);
                });
                return $category;
            });

            return $this->resProvider->apiJsonResponse(!count($categories) ? 404 : 200, trans('message.success.success'),  $categories, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(500, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

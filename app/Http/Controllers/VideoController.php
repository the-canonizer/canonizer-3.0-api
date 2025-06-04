<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ResponseInterface;
use App\Http\Resources\ErrorResource;
use App\Http\Request\Validate;
use App\Models\Category;
use App\Models\Video;
use app\Models\ConsensusVideoPodcast;

class VideoController extends Controller
{
    public function __construct(ResponseInterface $respProvider)
    {
        $this->resProvider = $respProvider;
    }

    /**
     * @OA\Get(
     *   path="/videos",
     *   tags={"Videos"},
     *   summary="Retrieve a list of videos",
     *   description="Get a list of videos categorized by their respective categories.",
     *   operationId="getVideos",
     *   security={{"clientAuth":{}}},
     *   @OA\Response(
     *       response=200,
     *       description="Successful response",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer"),
     *           @OA\Property(property="message", type="string"),
     *           @OA\Property(property="error", type="string", nullable=true),
     *           @OA\Property(
     *               property="data",
     *               type="array",
     *               @OA\Items(
     *                   type="object",
     *                   @OA\Property(property="id", type="integer", description="Category ID"),
     *                   @OA\Property(property="name", type="string", description="Category name"),
     *                   @OA\Property(
     *                       property="videos",
     *                       type="array",
     *                       @OA\Items(
     *                           type="object",
     *                           @OA\Property(property="id", type="integer", description="Video ID"),
     *                           @OA\Property(property="title", type="string", description="Video title"),
     *                           @OA\Property(property="thumbnail", type="string", description="Video thumbnail URL")
     *                       )
     *                   )
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Something went wrong",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer"),
     *           @OA\Property(property="message", type="string"),
     *           @OA\Property(property="error", type="string", nullable=true),
     *           @OA\Property(property="data", type="string", nullable=true)
     *       )
     *   ),
     *   @OA\Response(
     *       response=500,
     *       description="Server error",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer"),
     *           @OA\Property(property="message", type="string"),
     *           @OA\Property(property="error", type="string", nullable=true),
     *           @OA\Property(property="data", type="string", nullable=true)
     *       )
     *   )
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
     * @OA\Get(
     *   path="/videos/{category}/{categoryId}",
     *   tags={"Videos"},
     *   summary="Retrieve a list of videos by category",
     *   description="Fetches videos for a given category ID, including their available resolutions.",
     *   operationId="getVideosByCategory",
     *   security={{"clientAuth":{}}},
     *   @OA\Parameter(
     *       name="category",
     *       in="path",
     *       required=true,
     *       description="The category name",
     *       @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *       name="categoryId",
     *       in="path",
     *       required=true,
     *       description="The ID of the category",
     *       @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful response",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer"),
     *           @OA\Property(property="message", type="string"),
     *           @OA\Property(property="error", type="string", nullable=true),
     *           @OA\Property(
     *               property="data",
     *               type="array",
     *               @OA\Items(
     *                   type="object",
     *                   @OA\Property(property="id", type="integer", description="Category ID"),
     *                   @OA\Property(property="name", type="string", description="Category name"),
     *                   @OA\Property(
     *                       property="videos",
     *                       type="array",
     *                       @OA\Items(
     *                           type="object",
     *                           @OA\Property(property="id", type="integer", description="Video ID"),
     *                           @OA\Property(property="title", type="string", description="Video title"),
     *                           @OA\Property(property="thumbnail", type="string", description="Video thumbnail URL"),
     *                           @OA\Property(
     *                               property="resolutions",
     *                               type="array",
     *                               @OA\Items(
     *                                   type="object",
     *                                   @OA\Property(property="link", type="string", description="Resolution-specific video link")
     *                               )
     *                           )
     *                       )
     *                   )
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Something went wrong",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer"),
     *           @OA\Property(property="message", type="string"),
     *           @OA\Property(property="error", type="string", nullable=true),
     *           @OA\Property(property="data", type="string", nullable=true)
     *       )
     *   ),
     *   @OA\Response(
     *       response=500,
     *       description="Server error",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer"),
     *           @OA\Property(property="message", type="string"),
     *           @OA\Property(property="error", type="string", nullable=true),
     *           @OA\Property(property="data", type="string", nullable=true)
     *       )
     *   )
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

    public function getConsensusVideoPodcasts(Request $request){
        try {
            $perPage = $request->per_page ?? config('global.per_page');
            $consensusVideoPodcasts = ConsensusVideoPodcast::where('active', '1')->orderBy('id', 'DESC')->orderBy('id', $request->input('sort_by', 'DESC'))
                ->paginate($perPage);
            $collection = Util::getPaginatorResponse($consensusVideoPodcasts);
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $collection, null);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

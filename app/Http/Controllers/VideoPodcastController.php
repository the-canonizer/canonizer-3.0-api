<?php

namespace App\Http\Controllers;

use App\Models\VideoPodcast;
use Illuminate\Support\Facades\Cache;

class VideoPodcastController extends Controller
{
   
    /**
     * @OA\GET(
     *   path="/get-whats-new-content",
     *   tags={"News Section"},
     *   summary="Get data for What's New section",
     *   description="This API fetches data for the 'What's New' section.",
     *   operationId="GetDataForNewSection",
     *   security={{"clientAuth":{}}},
     *   @OA\Response(
     *       response=200,
     *       description="Successful operation",
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
     *                   @OA\Property(property="id", type="integer", description="Podcast ID"),
     *                   @OA\Property(property="title", type="string", description="Podcast title"),
     *                   @OA\Property(property="thumbnail", type="string", description="Podcast thumbnail URL"),
     *                   @OA\Property(property="description", type="string", description="Podcast description")
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Exception occurs",
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

    public function getNewContent()
    {
        try {
            $videoPodcast = Cache::remember('video_podcast_new_content', 1800, function () {
                return VideoPodcast::all();
            });
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $videoPodcast, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
       
    }

}

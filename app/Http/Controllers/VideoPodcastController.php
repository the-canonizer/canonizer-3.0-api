<?php

namespace App\Http\Controllers;

use App\Http\Resources\SuccessResource;
use App\Http\Resources\ErrorResource;
use App\Models\VideoPodcast;

class VideoPodcastController extends Controller
{

     /**
     * @OA\Post(path="/get_whats_new_content",
     *   tags={"NewSection"},
     *   summary="Get data for What's new section",
     *   description="This api used to get data for What's new section",
     *   operationId="GetDataForNewSection",
     *   @OA\Response(response=200,description="successful operation",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="data",
     *                                         type="string"
     *                                    )
     *                                 )
     *                            )
     *
     *   @OA\Response(response=400, description="Exception occurs",
     *                             @OA\JsonContent(
     *                                 type="array",
     *                                 @OA\Items(
     *                                         name="status_code",
     *                                         type="integer"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="message",
     *                                         type="string"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="error",
     *                                         type="array"
     *                                    ),
     *                                    @OA\Items(
     *                                         name="data",
     *                                         type="string"
     *                                    )
     *                                 )
     *                             )
     * 
     * )
     */

     /**
      * Get data for What's new section
      */
    public function getNewContent()
    {
        try {
            $videoPodcast = VideoPodcast::all();
            $res = (object) [
                "status_code" => 200,
                "message" => "Success",
                "error" => null,
                "data" => $videoPodcast,
            ];
            return (new SuccessResource($res))->response()->setStatusCode(200);
        } catch (\Throwable $e) {
            $res = (object) [
                "status_code" => 400,
                "message" => "Something went wrong",
                "error" => $e->getMessage(),
                "data" => null,
            ];
            return (new ErrorResource($res))->response()->setStatusCode(400);
        }
       
    }

}

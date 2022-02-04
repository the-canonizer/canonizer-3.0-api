<?php

namespace App\Http\Controllers;

use App\Http\Resources\SuccessResource;
use App\Http\Resources\ErrorResource;
use App\Models\SocialMediaLink;

class SocialMediaLinkController extends Controller
{

     /**
     * @OA\Post(path="/get_social_media_links",
     *   tags={"social media", "links"},
     *   summary="Get social media links",
     *   description="This api used to get social media links",
     *   operationId="GetAllSocialLinks",
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
     *                                         type="array"
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
      * Get all social media links
      */
    public function getLinks()
    {
        try {
            $socialMediaLinks = SocialMediaLink::orderBy('order_number')->get();
            $res = (object) [
                "status_code" => 200,
                "message" => "Success",
                "error" => null,
                "data" => $socialMediaLinks,
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

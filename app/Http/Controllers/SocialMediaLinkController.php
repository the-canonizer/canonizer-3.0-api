<?php

namespace App\Http\Controllers;

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
            return $this->resProvider->apiJsonResponse(200, 'Success', $socialMediaLinks, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, "Something went wrong", '', $e->getMessage());
        }

    }
}

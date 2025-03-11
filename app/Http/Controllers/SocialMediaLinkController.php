<?php

namespace App\Http\Controllers;

use App\Models\SocialMediaLink;

class SocialMediaLinkController extends Controller
{


    /**
     * @OA\Get(
     *     path="/get-social-media-links",
     *     tags={"Social Media Links"},
     *     summary="Get social media links",
     *     description="This API retrieves a list of social media links.",
     *     operationId="GetAllSocialLinks",
     *     security={{"clientAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status_code",
     *                 type="integer",
     *                 example=200
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Success"
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 nullable=true,
     *                 example=null
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         description="Unique identifier of the social media link",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="label",
     *                         type="string",
     *                         description="Name of the social media platform",
     *                         example="Facebook"
     *                     ),
     *                     @OA\Property(
     *                         property="link",
     *                         type="string",
     *                         format="url",
     *                         description="URL of the social media page",
     *                         example="https://www.facebook.com/pages/Canonizer.com/103927141540408/"
     *                     ),
     *                     @OA\Property(
     *                         property="icon",
     *                         type="string",
     *                         description="Icon path for the social media link",
     *                         example="/social-media/facebook.svg"
     *                     ),
     *                     @OA\Property(
     *                         property="order_number",
     *                         type="integer",
     *                         description="Display order of the social media link",
     *                         example=1
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Exception occurs",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="status_code",
     *                 type="integer",
     *                 example=400
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="An error occurred"
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Error details"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="string",
     *                 nullable=true,
     *                 example=null
     *             )
     *         )
     *     )
     * )
     */

    public function getLinks()
    {
        try {
            $socialMediaLinks = SocialMediaLink::orderBy('order_number')->get();
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $socialMediaLinks, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

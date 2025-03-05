<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PrivacyPolicy;


class PrivacyPolicyController extends Controller
{
    /**
     * @OA\Get(
     *   path="/privacy-policy",
     *   tags={"Privacy Policy"},
     *   summary="Get Privacy Policy Content",
     *   description="This API retrieves the full privacy policy content.",
     *   operationId="getPrivacyPolicyContent",
     *   security={{"clientAuth":{}}},
     *   @OA\Response(
     *       response=200,
     *       description="Privacy policy content retrieved successfully",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Success"),
     *           @OA\Property(
     *               property="data",
     *               type="array",
     *               @OA\Items(
     *                   type="object",
     *                   @OA\Property(property="id", type="integer", example=1),
     *                   @OA\Property(property="content", type="string", example="This is the privacy policy content."),
     *                   @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-04T12:00:00Z"),
     *                   @OA\Property(property="updated_at", type="string", format="date-time", example="2024-03-04T12:30:00Z")
     *               )
     *           ),
     *           @OA\Property(property="error", type="string", nullable=true)
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Exception occurred",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="An error occurred"),
     *           @OA\Property(property="error", type="string", example="Database connection failed")
     *       )
     *   )
     * )
     */

    public function getPrivacyPolicyContent()
    {
        try {
            $privacyPolicy = PrivacyPolicy::all();
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $privacyPolicy, '');
        } catch (\Throwable $e) {
            
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TermAndServices;

class TermAndServicesController extends Controller
{
    /**
     * @OA\Get(
     *     path="/get-terms-and-services-content",
     *     tags={"Terms and Services"},
     *     summary="Get Terms and Services Content",
     *     description="Retrieve the content of the Terms and Services.",
     *     operationId="getTermAndServicesContent",
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
     *                         description="Unique ID of the Terms and Services content"
     *                     ),
     *                     @OA\Property(
     *                         property="terms_and_services_content",
     *                         type="string",
     *                         description="Title of the Terms and Services section"
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
     *                 example="An exception occurred"
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Error message details"
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

    public function getTermAndServicesContent()
    {
        try {
            $termAndServices = TermAndServices::all();
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $termAndServices, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

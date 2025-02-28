<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Helpers\ResponseInterface;
use App\Models\EmbeddedCodeTracking;
use App\Http\Request\ValidationRules;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Http\Request\ValidationMessages;

class EmbeddedCodeController extends Controller
{

    private ValidationRules $rules;

    private ValidationMessages $validationMessages;

    public function __construct(ResponseInterface $resProvider)
    {
        $this->rules = new ValidationRules;
        $this->validationMessages = new ValidationMessages;
        $this->resProvider = $resProvider;
    }

    /**
 * @OA\Post(
 *     path="/embedded-code-tracking",
 *     summary="Create Embedded Code Tracking",
 *     tags={"Embedded Code"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="url", type="string", example="http://example.com"),
 *             @OA\Property(property="ip_address", type="string", example="192.168.1.1"),
 *             @OA\Property(property="user_agent", type="string", example="Mozilla/5.0")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Success",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="integer", example=200),
 *             @OA\Property(property="message", type="string", example="Success"),
 *             @OA\Property(property="data", ref="#/components/schemas/EmbeddedCodeTracking")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad Request",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="integer", example=400),
 *             @OA\Property(property="message", type="string", example="Validation errors or exception message"),
 *             @OA\Property(property="errors", type="object")
 *         )
 *     )
 * ),
 * @OA\Schema(
 *     schema="EmbeddedCodeTracking",
 *     type="object",
 *     description="Embedded Code Tracking Schema",
 *     @OA\Property(property="id", type="integer", example=1, description="Unique identifier"),
 *     @OA\Property(property="url", type="string", example="http://example.com", description="Tracked URL"),
 *     @OA\Property(property="ip_address", type="string", example="192.168.1.1", description="IP address of user"),
 *     @OA\Property(property="user_agent", type="string", example="Mozilla/5.0", description="User agent string"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-02-28T12:34:56Z", description="Timestamp of creation"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-02-28T12:34:56Z", description="Timestamp of last update")
 * )
 */

    public function createEmbeddedCodeTracking(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getEmbeddedCodeTrackingRules(), $this->validationMessages->getEmbeddedCodeTrackingMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        try {
            $input = [
                "url" => $request->url,
                "ip_address" => $request->ip_address,
                "user_agent" => $request->user_agent,
            ];
            $embeddedCodeTracking = EmbeddedCodeTracking::create($input);
            if ($embeddedCodeTracking) {
                $status = 200;
                $message = trans('message.success.success');
            }
            return $this->resProvider->apiJsonResponse($status, $message, $embeddedCodeTracking, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.exception');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Models\Algorithm;
use Illuminate\Support\Facades\Cache;

class AlgorithmController extends Controller
{

/**
 * @OA\Get(
 *     path="/get-algorithms",
 *     tags={"algorithms"},
 *     summary="Get all algorithms",
 *     description="This API retrieves a list of algorithms",
 *     operationId="GetAllAlgorithms",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="status_code",
 *                 type="integer"
 *             ),
 *             @OA\Property(
 *                 property="message",
 *                 type="string"
 *             ),
 *             @OA\Property(
 *                 property="error",
 *                 type="string",
 *                 nullable=true
 *             ),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(
 *                         property="id",
 *                         type="integer",
 *                         description="Unique identifier of the algorithm"
 *                     ),
 *                     @OA\Property(
 *                         property="algorithm_key",
 *                         type="string",
 *                         description="Key of the algorithm"
 *                     ),
 *                     @OA\Property(
 *                         property="algorithm_label",
 *                         type="string",
 *                         description="Label of the algorithm"
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
 *                 type="integer"
 *             ),
 *             @OA\Property(
 *                 property="message",
 *                 type="string"
 *             ),
 *             @OA\Property(
 *                 property="error",
 *                 type="string"
 *             ),
 *             @OA\Property(
 *                 property="data",
 *                 type="string",
 *                 nullable=true
 *             )
 *         )
 *     )
 * )
 */


    public function getAll()
    {
        try {
            $cacheKey = 'all_algorithm';
            $algorithms = Cache::remember($cacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () {
                return Algorithm::all();
            });
            if(count($algorithms) < 1)
                return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.algorithms_not_found'));

            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $algorithms, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}
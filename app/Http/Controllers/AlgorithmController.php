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
     *     path="/api/algorithms",
     *     summary="Get all algorithms",
     *     tags={"Algorithms"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Algorithm")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Algorithms not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Algorithms not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Exception",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Exception message")
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
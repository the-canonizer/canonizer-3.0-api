<?php

namespace App\Http\Controllers;

use App\Models\Namespaces;
use Illuminate\Support\Facades\Cache;


class NamespaceController extends Controller
{
    /**
     * @OA\Post(path="/get_all_namespaces",
     *   tags={"namespaces"},
     *   summary="Get all namespaces",
     *   description="This api used to get all the namespaces available in system.",
     *   operationId="GetAllNamespaces",
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
     * Get all namespaces
     */
    public function getAll()
    {
        try {
            $cacheKey = 'all_namespaces';
            $namespaces = Cache::remember($cacheKey, (int)env('CACHE_TIMEOUT_IN_SECONDS'), function () {
                $namespaces = Namespaces::orderBy('sort_order', 'ASC')->get();
                foreach ($namespaces as $namespace) {
                    $namespace->label = Namespaces::getNamespaceLabel($namespace, $namespace->name);
                }
                return $namespaces;
            });
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $namespaces, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Namespaces;
use Illuminate\Support\Facades\Cache;


class NamespaceController extends Controller
{
    /**
     * @OA\Get(
     *   path="/get-all-namespaces",
     *   tags={"Canon"},
     *   summary="Get all canons",
     *   description="This API retrieves all available canons in the system.",
     *   operationId="GetAllCanons",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *       response=200,
     *       description="Successful operation",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer"),
     *           @OA\Property(property="message", type="string"),
     *           @OA\Property(property="error", type="string", nullable=true),
     *           @OA\Property(
     *               property="data",
     *               type="array",
     *               @OA\Items(
     *                   type="object",
     *                   @OA\Property(property="id", type="integer"),
     *                   @OA\Property(property="name", type="string"),
     *                   @OA\Property(property="label", type="string"),
     *                   @OA\Property(property="sort_order", type="integer")
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Exception occurs",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer"),
     *           @OA\Property(property="message", type="string"),
     *           @OA\Property(property="error", type="string", nullable=true),
     *           @OA\Property(property="data", type="string", nullable=true)
     *       )
     *   )
     * )
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

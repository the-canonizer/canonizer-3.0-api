<?php

namespace App\Http\Controllers;


use App\Helpers\ResponseInterface;
use App\Models\Namespaces;


class NamespaceController extends Controller
{

    public function __construct(ResponseInterface $resProvider)
    {
        $this->resProvider = $resProvider;
    }
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
            $namespaces = Namespaces::all();
            return $this->resProvider->apiJsonResponse(200, 'Success', $namespaces, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, "Something went wrong", '', $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;


use App\Helpers\ResponseInterface;
use App\Models\Namespaces;


class NicknameController extends Controller
{

    public function __construct(ResponseInterface $resProvider)
    {
        $this->resProvider = $resProvider;
    }
    /**
     * @OA\Post(path="/get_all_nicknames",
     *   tags={"nickname"},
     *   summary="Get all nicknames",
     *   description="This api used to get all the namespaces available in system.",
     *   operationId="getAllNicknames",
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
     * Get all nicknames
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

    public function addNickName(){
        die('gsdgd');
    }

    
}

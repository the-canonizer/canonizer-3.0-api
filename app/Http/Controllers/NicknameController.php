<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddNickNameRequest;
use App\Http\Requests\UpdateNickNameRequest;
use App\Models\Nickname;
use Illuminate\Http\Request;


class NicknameController extends Controller
{
    /**
     * @OA\POST(path="/add_nick_name",
     *   tags={"User"},
     *   summary="Add New nick name",
     *   description="",
     *   operationId="addNickName",
     *   @OA\RequestBody(
     *     required=true,
     *     description="Unique nickname required with max 50 characters",
     *     @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="nick_name",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="visibility_status",
     *                  type="integer"
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated"
     *    ),
     *   @OA\Response(
     *     response=200,
     *     description="success",
     *     @OA\Schema(ref="#/components/schemas/Nickname")
     *    ),
     * )
     *
     * @OA\Schema(
     *     schema="NickName",
     *     title="NickName Schema to return for API's",
     * 	    @OA\Property(
     *         property="id",
     *         type="integer"
     *     ),
     * 	    @OA\Property(
     *         property="owner_code",
     *         type="string"
     *      ),
     *      @OA\Property (
     *          property="nick_name",
     *          type="string"
     *      ),
     *      @OA\Property (
     *          property="create_time",
     *          type="integer"
     *      ),
     *      @OA\Property (
     *          property="private",
     *          type="integer"
     *      )
     * )
     */
    public function addNickName(AddNickNameRequest $request)
    {
        $user = $request->user();

        try {

            $nickname = Nickname::createNickname($user->id, $request->all());
            return $this->resProvider->apiJsonResponse(200, config('message.success.nick_name_add'), $nickname, '');

        } catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, config('message.error.exception'), '', $e->getMessage());
        }

    }

    /**
     * @OA\POST(path="/update_nick_name",
     *   tags={"nickname"},
     *   summary="update nick name visibility status",
     *   description="",
     *   operationId="updatenickName",
     *   @OA\RequestBody(
     *     required=true,
     *     description="Unique nickname required with max 50 characters",
     *     @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *              @OA\Property(
     *                  property="visibility_status",
     *                  type="integer"
     *              )
     *          )
     *     ),
     *   ),
     *
     *   @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *   @OA\Response(response=200, description="Update successfully",  @OA\Schema(ref="#/components/schemas/Nickname"))
     * )
     */
    public function updateNickName($id, UpdateNickNameRequest $request){

        try {
            $nickname = Nickname::findOrFail($id);
            $nickname->private = $request->visibility_status;
            $nickname->update();

            return $this->resProvider->apiJsonResponse(200, config('message.success.nick_name_update'), $nickname, '');

        } catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, config('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Get(path="/get_all_nickname",
     *   tags={"User"},
     *   summary="Get list of all the nicknames",
     *   description="Get list of nicknames",
     *   operationId="getAllNickNames",
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Something went wrong")
     * )
     */
    public function getNickNameList(Request $request)
    {
        $user = $request->user();
        try {
            $allNicknames = Nickname::getAllNicknames($user->id);

            return $this->resProvider->apiJsonResponse(200, config('message.success.success'), $allNicknames, '');

        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, config('message.error.exception'), '', $e->getMessage());
        }

    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddNickNameRequest;
use App\Http\Requests\SetDefaultNicknameRequest;
use App\Http\Requests\UpdateNickNameRequest;
use App\Models\Nickname;
use Illuminate\Http\Request;


class NicknameController extends Controller
{
    /**
     * @OA\POST(path="/add-nick-name",
     *   tags={"Nickname"},
     *   summary="Add New nick name",
     *   description="",
     *   operationId="addNickName",
     *   security={{"clientAuth":{}}},
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
     *              ),
     *              @OA\Property(
     *                  property="default",
     *                  type="integer",
     *                  example=0
     *              )
     *          ),
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
     *         property="user_id",
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
            return $this->resProvider->apiJsonResponse(200, trans('message.success.nick_name_add'), $nickname, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/set-default-nick-name",
     *     tags={"Nickname"},
     *     summary="Set a nickname as default",
     *     description="Sets a specific nickname as the default for the authenticated user.",
     *     operationId="setDefaultNickName",
     *     security={{"clientAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Nickname ID to be set as default",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"nick_name_id"},
     *             @OA\Property(property="nick_name_id", type="integer", example=1, description="ID of the nickname to be set as default")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nickname successfully set as default",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Default nickname set successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=10),
     *                 @OA\Property(property="nick_name", type="string", example="JohnDoe"),
     *                 @OA\Property(property="is_default", type="boolean", example=true),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-03T12:00:00Z")
     *             ),
     *             @OA\Property(property="error", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         ref="#/components/responses/400BadRequest"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nickname not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Nickname not found"),
     *             @OA\Property(property="error", type="string", nullable=true)
     *         )
     *     )
     * )
     */


    public function setDefaultNickName(SetDefaultNicknameRequest $request)
    {
        try {
            $nickname = Nickname::setDefaultNickname($request->nick_name_id);
            return $this->resProvider->apiJsonResponse(200, trans('message.success.default_nick_name'), $nickname, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\POST(
     *   path="/update-nick-name/{id}",
     *   tags={"Nickname"},
     *   summary="Update nickname visibility status",
     *   description="Updates the visibility status of a nickname.",
     *   operationId="updateNickName",
     *   security={{"clientAuth":{}}},
     *   @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       description="Nickname ID",
     *       @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     description="Unique nickname required with max 50 characters",
     *     @OA\JsonContent(
     *         @OA\Property(property="visibility_status", type="integer", example=1)
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Something went wrong"),
     *         @OA\Property(property="errors", type="object")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Update successful",
     *     @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="user_id", type="integer", example=123),
     *         @OA\Property(property="nickname", type="string", example="JohnDoe"),
     *         @OA\Property(property="visibility_status", type="integer", example=1, description="1 for visible, 0 for hidden"),
     *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-03-04T12:34:56Z"),
     *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-03-05T14:56:30Z")
     *     )
     *   )
     * )
     */


    public function updateNickName($id, UpdateNickNameRequest $request){

        try {
            $nickname = Nickname::findOrFail($id);
            $nickname->private = $request->visibility_status;
            $nickname->update();

            return $this->resProvider->apiJsonResponse(200, trans('message.success.nick_name_update'), $nickname, '');

        } catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/get-nick-name-list",
     *     tags={"Nickname"},
     *     summary="Get a list of nicknames",
     *     description="Retrieves all nicknames associated with the authenticated user.",
     *     operationId="getNickNameList",
     *     security={{"clientAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nick_name", type="string", example="JohnDoe"),
     *                     @OA\Property(property="user_id", type="integer", example=10),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-03T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-03T12:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(property="error", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         ref="#/components/responses/400BadRequest"
     *     )
     * )
     */

    public function getNickNameList(Request $request)
    {
        $user = $request->user();
        try {
            $allNicknames = Nickname::getAllNicknames($user->id);
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $allNicknames, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
 
    /**
     * @OA\Get(
     *     path="/get-nick-support-user/{nick_id}",
     *     tags={"Nickname"},
     *     summary="Get users who support a given nickname",
     *     description="Retrieves the list of users who support a specific nickname.",
     *     operationId="getNickSupportUser",
     *     security={{"clientAuth":{}}},
     *     @OA\Parameter(
     *         name="nick_id",
     *         in="path",
     *         required=true,
     *         description="ID of the nickname",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=10),
     *                     @OA\Property(property="nick_name", type="string", example="JohnDoe"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-03T12:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-03-03T12:00:00Z")
     *                 )
     *             ),
     *             @OA\Property(property="error", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nickname not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Record not found"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="error", type="string", example="Nickname not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         ref="#/components/responses/400BadRequest"
     *     )
     * )
     */

    public function getNickSupportUser(Request $request,$nick_id)
    {
        $user = $request->user();
        try {
            $allNicknames = Nickname::getNickSupportUser($user,$nick_id);
            if ($allNicknames == 'not_found') {
                return $this->resProvider->apiJsonResponse(404, '', null, trans('message.error.record_not_found'));
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $allNicknames, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Namespaces;
use App\Http\Requests\AddNickNameRequest;
use App\Http\Requests\UpdateNickNameRequest;
use App\Models\Nickname;
use Illuminate\Http\Request;


class NicknameController extends Controller
{
    /**
     * @OA\Get(path="/add_nick_name",
     *   tags={"nickname"},
     *   summary="Add New nick name",
     *   description="",
     *   operationId="addNickName",
     *   @OA\Parameter(
     *     name="nick_name",
     *     required=true,
     *     in="query",
     *     description="Unique nickname required with max 50 characters",
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="visibility_status",
     *     in="query",
     *     @OA\Schema(
     *         type="integer",
     *     ),
     *     description="Visibility status required",
     *   ),
     *   
     *   @OA\Response(response=400, description="Something went wrong")
     *   @OA\Response(response=200, description="success",  @OA\Schema(ref="#/components/schemas/Nickname"))
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
     * @OA\Get(path="/update_nick_name",
     *   tags={"nickname"},
     *   summary="update nick name visibility status",
     *   description="",
     *   operationId="updatenickName", 
     *   @OA\Parameter(
     *     name="visibility_status",
     *     in="query",
     *     @OA\Schema(
     *         type="integer",
     *     ),
     *     description="Visibility status required",
     *   ),
     *   
     *   @OA\Response(response=400, description="Something went wrong")
     *   @OA\Response(response=200, description="Update successfully",  @OA\Schema(ref="#/components/schemas/Nickname"))
     * )
     */
    public function updateNickName($id, UpdateNickNameRequest $request){
        $user = $request->user();
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
     *   tags={"nickname"},
     *   summary="",
     *   description="Get list of nicknames",
     *   operationId="languages",
     *   @OA\Response(response=200, description="Sucsess")
     *   @OA\Response(response=400, description="Something went wrog")
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

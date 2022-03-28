<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddFolderRequest;
use App\Models\FileFolder;
use Illuminate\Http\Request;


class UploadController extends Controller
{
    /**
     * @OA\POST(path="/add-folder",
     *   tags={"uploads"},
     *   summary="Add New folder",
     *   description="",
     *   operationId="addFolder",
     *   @OA\RequestBody(
     *     required=true,
     *     description="folder name is required.",     *    
     *   ),
     *  @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *   @OA\Response(response=200, description="folder created successfully",  @OA\Schema(ref="#/components/schemas/FileFolder"))
     * )
     */
    public function addFolder(AddFolderRequest $request)
    {
        $user = $request->user();
        $all = $request->all();
        //echo "<pre>"; print_r($all); exit;

        try {

            $folder = new FileFolder();
            $folder->name = $all['name'];
            $folder->user_id = $user->id;
            $folder->created_at = time();
            $folder->updated_at = time();
            $folder->save();

            return $this->resProvider->apiJsonResponse(200, trans('message.uploads.folder_created'), $folder, '');

        } catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }

    }
}

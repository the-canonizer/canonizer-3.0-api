<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddFolderRequest;
use App\Models\FileFolder;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Upload;
use App\Helpers\Aws;
use App\Helpers\Util;
use App\Http\Request\ValidationRules;
use App\Http\Request\ValidationMessages;
use App\Helpers\ResponseInterface;
use App\Http\Request\Validate;
use App\Http\Resources\ErrorResource;
use App\Http\Resources\SuccessResource;
use App\Models\Statement;


class UploadController extends Controller
{

    private ValidationRules $rules;

    private ValidationMessages $validationMessages;

    public function __construct(ResponseInterface $resProvider)
    {
        $this->rules = new ValidationRules;
        $this->validationMessages = new ValidationMessages;
        $this->resProvider = $resProvider;
    }

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

        try {

            if(isset($all['id']) && $all['id']){
                $folder = FileFolder::where('id',$all['id'])->first();
                $folder->name = $all['name'];
                $folder->update();
                return $this->resProvider->apiJsonResponse(200, trans('message.uploads.folder_name_updated'), $folder, '');
            }

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

    /**
     * @OA\Post(path="/upload-files",
     *   tags={"upload"},
     *   summary="Upload files to s3 ",
     *   description="This is used to upload files in bulk",
     *   operationId="uploadFiles",
     *    @OA\RequestBody(
     *     required=true,
     *     description="",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(     *              
     *              @OA\Property(
     *                  property="file",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="name",
     *                  type="string"
     *              ),
     *              @OA\Property(
     *                  property="folder_id",
     *                  type="integer"
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(response=200, description="Files Uploaded Successfully"),
     *   @OA\Response(response=400, description="Something went wrong")
     *  )
     */
    public function uploadFileToS3(Request $request, Validate $validate) 
    {
        $validationErrors = $validate->validate($request, $this->rules->getUploadFileValidationRules(), $this->validationMessages->getUploadFileValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }


        $all = $request->all();
        $user = $request->user();

        try{
            $uploadFiles = [];
            foreach($all['file'] as $k => $file){ 
                $six_digit_random_number = random_int(100000, 999999);
                $filename = User::ownerCode($user->id) . '_' . time() . '_' . $six_digit_random_number  .'.' . $file->getClientOriginalExtension(); 
              
                /** Upload File to S3 */
                $result = Aws::UploadFile($filename,$file);
                $response = $result->toArray();       

                $fileShortCode = Util::generateShortCode();
                $data = [
                    'file_name' => trim($all['name'][$k]),
                    'user_id' => $user->id,
                    'short_code' => $fileShortCode, 
                    'file_id' => $fileShortCode,
                    'file_type'=> $file->getMimeType(),
                    'folder_id'=> (isset($all['folder_id']) && !empty($all['folder_id'])) ? $all['folder_id'] : null,
                    'file_path' => $response['ObjectURL'],
                    'created_at' => time(),
                    'updated_at' => time()
                ];
                array_push($uploadFiles,$data);

            }
            Upload::insert($uploadFiles);

            return $this->resProvider->apiJsonResponse(200, trans('message.uploads.success'), '', '');

        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
  


    /**
     * @OA\Delete(path="/folder/delete/{id}",
     *   tags={"upload"},
     *   summary="Delete  folder",
     *   description="This API is used to delete a created folder if no file exists inside that folder",
     *   operationId="folderDelete",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *   @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Delete a record from this id",
     *         @OA\Schema(
     *              type="integer"
     *         ) 
     *    ),
     *     @OA\Response(
     *         response=200,
     *        description = "Success",
     *        @OA\JsonContent(
     *             type="object",
     *              @OA\Property(
     *                   property="status_code",
     *                   type="integer"
     *               ),
     *               @OA\Property(
     *                   property="message",
     *                   type="string"
     *               ),
     *              @OA\Property(
     *                   property="error",
     *                   type="string"
     *              ),
     *             @OA\Property(
     *                property="data",
     *                type="string",
     *             ),
     *        ),
     *     ),
     *
     *
     *     @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     * )
     */
    public function folderDelete($id)
    {
        try{

            $files = Upload::where('folder_id','=', $id)->get();

            if(count($files) > 0){
                $status = 400;
                $message = trans('message.uploads.folder_has_files_can_not_delete');
            }else{
                $folder = FileFolder::where('id',$id)->first();

                if(!$folder){
                    return $this->resProvider->apiJsonResponse(400, trans('message.uploads.folder_not_found'), null, null);
                }

                $folder->delete();
                $status = 200;
                $message = trans('message.uploads.folder_deleted');
            }

            return $this->resProvider->apiJsonResponse($status, $message, null, null);

        }catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Get(path="/get-uploaded-files",
     *   tags={"upload"},
     *   summary="get uploaded files and folder",
     *   description="This is used to get uploaded files and folder with count of files uploaded inside folder.",
     *   operationId="getUploadedFiles",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *  @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Get all files of this folder id",
     *         @OA\Schema(
     *              type="integer"
     *         ) 
     *    ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */
    public function getUploadedFiles(Request $request)
    {
        $user = $request->user();
        try{
            $files = Upload::where('user_id','=', $user->id)->where('folder_id' ,'=', null)->get();
            $folders = FileFolder::withCount('uploads')->where('user_id', '=', $user->id)->get();

            $data = [
                'files' => $files,
                'folders' => $folders
            ];
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $data, null);
        }catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }

    /**
     * @OA\Get(path="/folder/files/{id}",
     *   tags={"upload"},
     *   summary="get uploaded files inside folder",
     *   description="This is used to get uploaded files inside a folder.",
     *   operationId="getUploadedFiles",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message")
     * )
     */
    public function getFolderFiles($id)
    {
        try{
            $folder = FileFolder::where('id',$id)->first();
            if(!$folder){
                return $this->resProvider->apiJsonResponse(200, trans('message.uploads.not_found'), null, null);
            }

            $files = Upload::where('folder_id', '=' ,$id)->orderBy('created_at','DESC')->get();
            return $this->resProvider->apiJsonResponse(400, trans('message.success.success'), $files, null);

        }catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }


    /**
     * @OA\Delete(path="/file/delete/{id}",
     *   tags={"upload"},
     *   summary="Delete  file",
     *   description="This API is used to delete a uploaded file if not used in any statement",
     *   operationId="folderDelete",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *    ),
     *   @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Delete a record from this id",
     *         @OA\Schema(
     *              type="integer"
     *         ) 
     *    ),
     *     @OA\Response(
     *         response=200,
     *        description = "Success",
     *        @OA\JsonContent(
     *             type="object",
     *              @OA\Property(
     *                   property="status_code",
     *                   type="integer"
     *               ),
     *               @OA\Property(
     *                   property="message",
     *                   type="string"
     *               ),
     *              @OA\Property(
     *                   property="error",
     *                   type="string"
     *              ),
     *             @OA\Property(
     *                property="data",
     *                type="string",
     *             ),
     *        ),
     *     ),
     *
     *
     *     @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *    @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     * )
     */
    public function fileDelete($id){
        try{

            $file = Upload::where('id',$id)->first();
            if(!$file){
                return $this->resProvider->apiJsonResponse(400, trans('message.uploads.file_not_found'), null, null);
            }

            $ifFileInuse = Statement::checkIfFileInUse($file->short_code);

            if($ifFileInuse){

                $status = 400;
                $message = trans('message.uploads.file_in_use');

            }else{

                $file->delete();
                $status = 200;
                $message = trans('message.uploads.file_deleted');
            }

            return $this->resProvider->apiJsonResponse($status, $message, null, null);

        }catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }



}

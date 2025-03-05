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
use Illuminate\Support\Carbon;

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
     * @OA\Post(
     *   path="/add-folder",
     *   tags={"Folder"},
     *   summary="Create or update a folder",
     *   description="Creates a new folder or updates an existing folder's name for the authenticated user.",
     *   operationId="addFolder",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *       required=true,
     *       description="Folder data",
     *       @OA\JsonContent(
     *           required={"name"},
     *           @OA\Property(
     *               property="name",
     *               type="string",
     *               description="The folder name",
     *               example="My Documents"
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Folder created or updated successfully",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=200),
     *           @OA\Property(property="message", type="string", example="Folder created successfully"),
     *           @OA\Property(
     *               property="data",
     *               type="object",
     *               @OA\Property(property="id", type="integer", example=123),
     *               @OA\Property(property="name", type="string", example="My Documents"),
     *               @OA\Property(property="user_id", type="integer", example=45),
     *               @OA\Property(property="created_at", type="integer", example=1683112333),
     *               @OA\Property(property="updated_at", type="integer", example=1683112333)
     *           ),
     *           @OA\Property(property="error", type="string", nullable=true)
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Error message",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="An error occurred"),
     *           @OA\Property(property="error", type="string", example="Detailed error message")
     *       )
     *   )
     * )
     */

        public function addFolder(AddFolderRequest $request)
        {
            $user = $request->user();
            $data = $request->only('id', 'name');

            try {
                if (!empty($data['id'])) {
                    $folder = FileFolder::find($data['id']);
                    if (!$folder) {
                        return $this->resProvider->apiJsonResponse(
                            404,
                            trans('message.uploads.folder_not_found'),
                            null,
                            ''
                        );
                    }
                    $folder->update(['name' => $data['name']]);
                    return $this->resProvider->apiJsonResponse(
                        200,
                        trans('message.uploads.folder_name_updated'),
                        $folder,
                        ''
                    );
                } else {
                    // If your model supports timestamps, you can remove the manual timestamp assignments
                    $folder = FileFolder::create([
                        'name'      => $data['name'],
                        'user_id'   => $user->id,
                        'created_at'=> time(),
                        'updated_at'=> time(),
                    ]);
                    return $this->resProvider->apiJsonResponse(
                        200,
                        trans('message.uploads.folder_created'),
                        $folder,
                        ''
                    );
                }
            } catch (\Throwable $e) {
                return $this->resProvider->apiJsonResponse(
                    400,
                    trans('message.error.exception'),
                    '',
                    $e->getMessage()
                );
            }
        }
        

    /**
     * @OA\Post(path="/upload-files",
     *   tags={"upload"},
     *   summary="Upload files to s3 ",
     *   description="This is used to upload files in bulk",
     *   operationId="uploadFiles",
     *   security={{"bearerAuth":{}}},
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
                $filename = $user->id . '_' . time() . '_' . $six_digit_random_number  .'.' . $file->getClientOriginalExtension(); 
              
                /** Upload File to S3 */
                $result = Aws::UploadFile($filename,$file);
                $response = $result->toArray();       

                $submittedFileName = trim($all['name'][$k]);
                $fileShortCode = Util::generateShortCode($file, $submittedFileName);
                $data = [
                    'file_name' => trim($all['name'][$k]),
                    'user_id' => $user->id,
                    'short_code' => $fileShortCode, 
                    'file_id' => $fileShortCode,
                    'file_type'=> $file->getMimeType(),
                    'folder_id'=> (isset($all['folder_id']) && !empty($all['folder_id'])) ? $all['folder_id'] : null,
                    'file_path' => $filename,
                    'created_at' => time(),
                    'updated_at' => time()
                ];
                array_push($uploadFiles,$data);

                $responseData[] = [
                    'file_name' => trim($all['name'][$k]),
                    'short_code' => $fileShortCode,
                    'file_id' => $fileShortCode,
                    'file_type' => $file->getMimeType(),
                    'file_path' => $filename,
                    'base_path' => env('SHORT_CODE_BASE_PATH'),
                    'short_code_path' => env('SHORT_CODE_BASE_PATH') . $filename, // Include short_code_path in the 
                ];
            }
             Upload::insert($uploadFiles);
            if($request->has('from_test_case')) {
                    $test_case_response = ["file_name" => $filename];
            }

            return $this->resProvider->apiJsonResponse(200, trans('message.uploads.success'), $responseData, '');

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
     *   security={{"bearerAuth":{}}},
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
     * @OA\Get(
     *   path="/uploaded-files",
     *   tags={"Files"},
     *   summary="Retrieve uploaded files and folders",
     *   description="Fetches the list of uploaded files and folders for the authenticated user.",
     *   operationId="getUploadedFiles",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *       response=200,
     *       description="Successful retrieval of files and folders",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(
     *               property="files",
     *               type="array",
     *               @OA\Items(
     *                   type="object",
     *                   @OA\Property(property="id", type="integer", example=1),
     *                   @OA\Property(property="file_name", type="string", example="document.pdf"),
     *                   @OA\Property(property="file_path", type="string", example="uploads/documents/document.pdf"),
     *                   @OA\Property(property="short_code_path", type="string", example="https://example.com/short/document.pdf"),
     *                   @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-04T12:34:56Z")
     *               )
     *           ),
     *           @OA\Property(
     *               property="folders",
     *               type="array",
     *               @OA\Items(
     *                   type="object",
     *                   @OA\Property(property="id", type="integer", example=10),
     *                   @OA\Property(property="name", type="string", example="My Folder"),
     *                   @OA\Property(property="uploads_count", type="integer", example=5),
     *                   @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-04T10:20:30Z")
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Invalid request parameters")
     *       )
     *   ),
     *   @OA\Response(
     *       response=403,
     *       description="Unauthorized action",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=403),
     *           @OA\Property(property="message", type="string", example="Unauthorized access")
     *       )
     *   )
     * )
     */

    public function getUploadedFiles(Request $request)
    {
        $user = $request->user();
        //try{
            $files = Upload::where('user_id','=', $user->id)->where('folder_id' ,'=', null)->orderBy('created_at', 'desc')->get();
            $folders = FileFolder::withCount('uploads')->where('user_id', '=', $user->id)->orderBy('created_at', 'desc')->get();
            foreach($files as $val){
                $s3FileName = $val->file_name;
                if(isset($val->file_path) && $val->file_path)
                {
                    $strArray = explode('/',$val->file_path);
                    $s3FileName = end($strArray);
                }
                
                $val->short_code_path = env('SHORT_CODE_BASE_PATH').$s3FileName;
            } 
            $data = [
                'files' => $files,
                'folders' => $folders
            ];
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $data, null);
        //}catch (\Throwable $e) {

            //return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        //}
    }

    /**
     * @OA\Get(
     *   path="/folder/files/{id}",
     *   tags={"Files"},
     *   summary="Retrieve files from a specific folder",
     *   description="Fetches the list of files within a specified folder by folder ID.",
     *   operationId="getFolderFiles",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *       name="id",
     *       in="path",
     *       required=true,
     *       description="ID of the folder",
     *       @OA\Schema(
     *           type="integer",
     *           example=10
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful retrieval of folder files",
     *       @OA\JsonContent(
     *           type="array",
     *           @OA\Items(
     *               type="object",
     *               @OA\Property(property="id", type="integer", example=1),
     *               @OA\Property(property="file_name", type="string", example="document.pdf"),
     *               @OA\Property(property="file_path", type="string", example="uploads/documents/document.pdf"),
     *               @OA\Property(property="short_code_path", type="string", example="https://example.com/short/document.pdf"),
     *               @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-04T12:34:56Z")
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request or Folder Not Found",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Folder not found")
     *       )
     *   ),
     *   @OA\Response(
     *       response=403,
     *       description="Unauthorized action",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=403),
     *           @OA\Property(property="message", type="string", example="Unauthorized access")
     *       )
     *   )
     * )
     */

    public function getFolderFiles($id)
    {
        try{
            $folder = FileFolder::where('id',$id)->first();
            if(!$folder){
                return $this->resProvider->apiJsonResponse(400, trans('message.uploads.not_found'), null, null);
            }

            $files = Upload::where('folder_id', '=' ,$id)->orderBy('created_at','DESC')->get();
            foreach($files as $val){
                $s3FileName = $val->file_name;
                if(isset($val->file_path) && $val->file_path)
                {
                    $strArray = explode('/',$val->file_path);
                    $s3FileName = end($strArray);
                }
                
                $val->short_code_path = env('SHORT_CODE_BASE_PATH').$s3FileName;
            } 
            
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $files, null);

        }catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }


    /**
     * @OA\Delete(path="/file/delete/{id}",
     *   tags={"upload"},
     *   summary="Delete  file",
     *   description="This API is used to delete a uploaded file if not used in any statement",
     *   operationId="fileDelete",
     *   security={{"bearerAuth":{}}},
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



    /**
     * @OA\Get(
     *   path="/global-search-uploaded-files",
     *   tags={"Files"},
     *   summary="Search uploaded files globally",
     *   description="Search for uploaded files based on file name and date range for the authenticated user.",
     *   operationId="getGlobalSearchUploadedFiles",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *       name="query",
     *       in="query",
     *       required=false,
     *       description="Search keyword for file name",
     *       @OA\Schema(
     *           type="string",
     *           example="document"
     *       )
     *   ),
     *   @OA\Parameter(
     *       name="date",
     *       in="query",
     *       required=false,
     *       description="Date filter in timestamp format",
     *       @OA\Schema(
     *           type="integer",
     *           example=1709078400
     *       )
     *   ),
     *   @OA\Response(
     *       response=200,
     *       description="Successful file search",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(
     *               property="status_code",
     *               type="integer",
     *               example=200
     *           ),
     *           @OA\Property(
     *               property="message",
     *               type="string",
     *               example="Success"
     *           ),
     *           @OA\Property(
     *               property="data",
     *               type="object",
     *               @OA\Property(
     *                   property="files",
     *                   type="array",
     *                   @OA\Items(
     *                       type="object",
     *                       @OA\Property(property="id", type="integer", example=1),
     *                       @OA\Property(property="file_name", type="string", example="report.pdf"),
     *                       @OA\Property(property="file_path", type="string", example="uploads/reports/report.pdf"),
     *                       @OA\Property(property="short_code_path", type="string", example="https://example.com/short/report.pdf"),
     *                       @OA\Property(property="created_at", type="string", format="date-time", example="2025-03-04T12:34:56Z"),
     *                       @OA\Property(
     *                           property="folder",
     *                           type="object",
     *                           nullable=true,
     *                           @OA\Property(property="id", type="integer", example=5),
     *                           @OA\Property(property="name", type="string", example="Documents")
     *                       )
     *                   )
     *               )
     *           )
     *       )
     *   ),
     *   @OA\Response(
     *       response=400,
     *       description="Bad Request",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=400),
     *           @OA\Property(property="message", type="string", example="Invalid parameters")
     *       )
     *   ),
     *   @OA\Response(
     *       response=403,
     *       description="Unauthorized action",
     *       @OA\JsonContent(
     *           type="object",
     *           @OA\Property(property="status_code", type="integer", example=403),
     *           @OA\Property(property="message", type="string", example="Unauthorized access")
     *       )
     *   )
     * )
     */
        
    public function getGlobalSearchUploadedFiles(Request $request)
    {
        $user = $request->user();
        $query = $request->get('query');
        $date = intval($request->get('date'));
        try {
            $files = Upload::where('user_id', $user->id)
                ->when($query, function ($q) use ($query) {
                    return $q->where('file_name', 'LIKE', '%' . $query . '%');
                })
                ->when($date, function ($q) use ($date) {
                    $startDay = Carbon::parse($date)->startOfDay()->timestamp;
                    $endDay = Carbon::parse($date)->endOfDay()->timestamp;
                    return $q->whereBetween('created_at', [$startDay, $endDay]);
                })->with('folder:id,name')->latest()->get();

            foreach ($files as $val) {
                $s3FileName = $val->file_name;
                if (isset($val->file_path) && $val->file_path) {
                    $strArray = explode('/', $val->file_path);
                    $s3FileName = end($strArray);
                }
                $val->short_code_path = env('SHORT_CODE_BASE_PATH') . $s3FileName;
            }
            $data = [
                'files' => $files,
            ];
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $data, null);
        } catch (\Throwable $e) {

            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

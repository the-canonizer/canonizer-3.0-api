<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddFolderRequest;
use Illuminate\Support\Facades\Storage;
use App\Models\FileFolder;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Upload;
use Aws\S3\S3Client;



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

    public function uploadFileToS3(Request $request) {
        $all = $request->all();
        $user = $request->user();

        try{

            $uploadFiles = [];

            foreach($all['file'] as $k => $file){
                $six_digit_random_number = random_int(100000, 999999);
                $filename = User::ownerCode($user->id) . '_' . time() . '_' . $six_digit_random_number  .'.' . $file->getClientOriginalExtension(); 
                
                $s3Client = new S3Client([
                    'version' => 'latest',
                    'region'  => 'us-east-2',
                    'credentials' => [
                    'key'    => 'AKIAXWMBTFWVOWZ6WAQ2',
                    'secret' => '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'
                    ]
                ]);
                    
                $result = $s3Client->putObject([
                    'Bucket' => 'canonizer-public-file',
                    'Key'    => $filename,
                    'Body'   => fopen($file, 'r'),
                ]);

                $response = $result->toArray();

                /*$s3 = Storage::disk('s3'); 
                $s3->put($filename, file_get_contents($file));
                $filePath = Storage::disk('s3')->url($filename);*/

                $data = [
                    'file_name' => trim($all['name'][$k]),
                    'user_id' => $user->id,
                    'short_code' => "can-" . $this->generate_string(), 
                    'file_id' => "can-" . $this->generate_string(),
                    'file_type'=> $file->getMimeType(),
                    'folder_id'=> isset($all['folder_id']) ? $all['folder_id'] : '',
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

    
 
    private function generate_string($strength = 9) {
        $input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $input_length = strlen($input);
        $random_string = '';
        for($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }
    
        return $random_string;
    }


}

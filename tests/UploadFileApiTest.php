<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Guzzle\Service\Resource\Model;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\FileFolder;

class UploadFileApiTest extends TestCase
{

    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testUnauthorizedUserCannotUpload(){
        print sprintf("\n Unauthorized User can not  request this api %d %s", 401,PHP_EOL);
        $response = $this->call('POST', '/api/v3/upload-files', []);
        $this->assertEquals(401, $response->status());
    }

    public function testUnauthorizedUserCannotDeleteFolder(){
        print sprintf("\n Unauthorized User can not  request this api %d %s", 401,PHP_EOL);
        $response = $this->call('delete', '/api/v3/folder/delete/1', []);
        $this->assertEquals(401, $response->status());
    }

    public function testUnauthorizedUserCannotDeleteFile(){
        print sprintf("\n Unauthorized User can not  request this api %d %s", 401,PHP_EOL);
        $response = $this->call('delete', '/api/v3/file/delete/1', []);
        $this->assertEquals(401, $response->status());
    }
    
    public function testFileUpload(){
        print sprintf(" \n S3 bulk upload test %d %s", 200,PHP_EOL);

        Storage::fake('s3');

        $user = User::factory()->make();
        $rand = rand(1000,99999);
        $input = [
                    'file' => [
                        UploadedFile::fake()->image($rand.'.jpg')
                        ],
                    'name' => [
                            'image1'
                        ]
                ];
        $response = $this->actingAs($user)->post('/api/v3/upload-files', $input);
        // dd($this->response);
        $this->assertEquals(200, $this->response->status());
        //$awsEndPoint = env('AWS_END_POINT');
        //Storage::disk('s3')->assertExists($awsEndPoint .'/'.$rand.'.jpg');
     
    }

    public function testGetFilesAndFolderApi(){
        print sprintf(" \n Fetch folder and files created  %d %s", 200,PHP_EOL);
        $user = User::factory()->make();

        $this->actingAs($user)->get('/api/v3/uploaded-files', []);
        $this->assertEquals(200, $this->response->status());
    }
}

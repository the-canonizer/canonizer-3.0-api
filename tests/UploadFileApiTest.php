<?php

use App\Facades\Aws;
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
    
    public function testFileUpload() {
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
            ],
            'from_test_case' => 1
        ];

        $this->actingAs($user)->post('/api/v3/upload-files', $input);
        $this->assertEquals(200, $this->response->status());
        
        if($this->response->status() == 200) {
            $uploaded_file_key = $this->response->getData()->data->file_name ?? "";
            $result = Aws::DeleteFile($uploaded_file_key);
            $this->assertEquals(204, $result['@metadata']['statusCode']);
        }
    }

    public function testGetFilesAndFolderApi(){
        print sprintf(" \n Fetch folder and files created  %d %s", 200,PHP_EOL);
        $user = User::factory()->make();

        $this->actingAs($user)->get('/api/v3/uploaded-files', []);
        $this->assertEquals(200, $this->response->status());
    }

    public function testUserProfileImageRequired() {
        $user = User::factory()->make();
        $input = [];
        $this->actingAs($user)->post('/api/v3/update-profile-picture', $input);
        $this->assertEquals(400, $this->response->status());
    }

    public function testUserProfileImageType() {
        $user = User::factory()->make();
        $rand = rand(1000,99999);
        $input = [
            'profile_picture' => UploadedFile::fake()->image($rand.'.gif')
        ];
        $this->actingAs($user)->post('/api/v3/update-profile-picture', $input);
        $this->assertEquals(400, $this->response->status());
    }

    public function testUserProfileImageIsNotFile() {
        $user = User::factory()->make();
        $input = [
            'profile_picture' => 'abc.jpg'
        ];
        $this->actingAs($user)->post('/api/v3/update-profile-picture', $input);
        $this->assertEquals(400, $this->response->status());
    }

    public function testUserProfileImageApiForAuth() {
        $this->post('/api/v3/update-profile-picture', []);
        $this->assertEquals(401, $this->response->status());
    }

    public function testUserProfileImageUpload() {

        $user = User::factory()->make();
        $rand = rand(1000,99999);
        $input = [
            'profile_picture' => UploadedFile::fake()->image($rand.'.jpeg')
        ];
        $this->actingAs($user)->post('/api/v3/update-profile-picture', $input);
        $this->assertEquals(200, $this->response->status());

        if($this->response->status() == 200) {
            $uploaded_file_key = explode("/", $this->response->getData()->data->profile_picture, 4) ?? [];
            $result = Aws::DeleteFile($uploaded_file_key[3] ?? "");
            $this->assertEquals(204, $result['@metadata']['statusCode']);
        }
    }
}

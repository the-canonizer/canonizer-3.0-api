<?php

namespace Tests;

use App\Facades\Aws;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
        $response = $this->post('/api/v3/upload-files', []);
        $response->assertStatus(401);
    }

    public function testUnauthorizedUserCannotDeleteFolder(){
        print sprintf("\n Unauthorized User can not  request this api %d %s", 401,PHP_EOL);
        $response = $this->delete('/api/v3/folder/delete/1', []);
        $response->assertStatus(401);
    }

    public function testUnauthorizedUserCannotDeleteFile(){
        print sprintf("\n Unauthorized User can not  request this api %d %s", 401,PHP_EOL);
        $response = $this->delete('/api/v3/file/delete/1', []);
        $response->assertStatus(401);
    }
    
    public function testFileUpload() {
        print sprintf(" \n S3 bulk upload test %d %s", 200,PHP_EOL);

        Storage::fake('s3');
        Aws::shouldReceive('UploadFile')->andReturn(new \Aws\Result(['@metadata' => ['statusCode' => 200]]));
        Aws::shouldReceive('DeleteFile')->andReturn(['@metadata' => ['statusCode' => 204]]);

        $user = User::factory()->create(['status' => 1]);
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

        $response = $this->actingAs($user)->post('/api/v3/upload-files', $input);
        $response->assertStatus(200);
        
        if($response->status() == 200) {
            $uploaded_file_key = $response->getData()->data[0]->file_path ?? "";
            $result = Aws::DeleteFile($uploaded_file_key);
            $this->assertEquals(204, $result['@metadata']['statusCode']);
        }
    }

    public function testGetFilesAndFolderApi(){
        print sprintf(" \n Fetch folder and files created  %d %s", 200,PHP_EOL);
        $user = User::factory()->create(['status' => 1]);

        $response = $this->actingAs($user)->get('/api/v3/uploaded-files', []);
        $response->assertStatus(200);
    }

    public function testUserProfileImageRequired() {
        $user = User::factory()->create(['status' => 1]);
        $input = [];
        $response = $this->actingAs($user)->post('/api/v3/update-profile-picture', $input);
        $response->assertStatus(400);
    }

    public function testUserProfileImageType() {
        $user = User::factory()->create(['status' => 1]);
        $rand = rand(1000,99999);
        $input = [
            'profile_picture' => UploadedFile::fake()->image($rand.'.gif')
        ];
        $response = $this->actingAs($user)->post('/api/v3/update-profile-picture', $input);
        $response->assertStatus(400);
    }

    public function testUserProfileImageIsNotFile() {
        $user = User::factory()->create(['status' => 1]);
        $input = [
            'profile_picture' => 'abc.jpg'
        ];
        $response = $this->actingAs($user)->post('/api/v3/update-profile-picture', $input);
        $response->assertStatus(400);
    }

    public function testUserProfileImageApiForAuth() {
        $response = $this->post('/api/v3/update-profile-picture', []);
        $response->assertStatus(401);
    }

    public function testUserProfileImageUpload() {
        Aws::shouldReceive('UploadFile')->andReturn(new \Aws\Result(['@metadata' => ['statusCode' => 200]]));
        Aws::shouldReceive('DeleteFile')->andReturn(['@metadata' => ['statusCode' => 204]]);

        $user = User::factory()->create(['status' => 1]);
        $rand = rand(1000,99999);
        $input = [
            'profile_picture' => UploadedFile::fake()->image($rand.'.jpeg')
        ];
        $response = $this->actingAs($user)->post('/api/v3/update-profile-picture', $input);
        $response->assertStatus(200);

        if($response->status() == 200) {
            $uploaded_file_key = explode("/", $response->getData()->data->profile_picture, 4) ?? [];
            $result = Aws::DeleteFile($uploaded_file_key[3] ?? "");
            $this->assertEquals(204, $result['@metadata']['statusCode']);
        }

        // Run the test of update profile image without deleting above one
        $rand = rand(1000,99999);
        $input = [
            'profile_picture' => UploadedFile::fake()->image($rand.'.jpeg'),
            'is_update' => 1
        ];

        $response = $this->actingAs($user)->post('/api/v3/update-profile-picture', $input);
        $response->assertStatus(200);
        
        if($response->status() == 200) {
            $uploaded_file_key = explode("/", $response->getData()->data->profile_picture, 4) ?? [];
            $result = Aws::DeleteFile($uploaded_file_key[3] ?? "");
            $this->assertEquals(204, $result['@metadata']['statusCode']);
        }
    }
}

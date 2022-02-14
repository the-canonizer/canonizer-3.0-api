<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\user;

class NickNameApiTest extends TestCase
{
    use DatabaseTransactions;

   
    /**
     * Guest user can not access add nick name api
     */
    public function testGuesUserCanoNotAddNickName()
    {
        print sprintf("Access denied for guest user %d %s", 302,PHP_EOL);
        $parameter = [
            'nick_name' => "test",
            'visibility_status' => 1
        ];
        $response = $this->call('POST', '/api/v3/add_nick_name', $parameter);
        $this->assertEquals(401, $response->status());       
    }

    public function testAddNickNameWithInvalidData(){
        print sprintf(" \n Add Nickname %d %s", 200,PHP_EOL);
        $user = User::factory()->make();

        $parameter = [
            'nick_name' => "",
            "visibility_status" => ""
        ];

        $this->actingAs($user)
        ->post('/api/v3/add_nick_name', $parameter);

        $this->assertEquals(400, $this->response->status());
    }

    public function testAddNickNameWithValidData(){

    }

    public function testGuestUserCanNotGetAllNickNames(){

    }

    public function testGetAllNickNamesWithAuthorizedUser(){
        
    }
}

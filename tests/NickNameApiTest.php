<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class NickNameApiTest extends TestCase
{
    use DatabaseTransactions;
    
    public function testValidateAddNickNameFields()
    {
        print sprintf("Add nick name Validation  %d %s", 401,PHP_EOL);

        $rules = [
            'nick_name' => 'required|unique:nick_name|max:50',
            'visibility_status' => 'required',
        ];
        
        $data = [
            "nick_name" => "nick_name",
            "visibility_status" => "1",
        ];
        
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testValidateUpdateNickNameFields()
    {
        print sprintf("Upodate nick name visibility Validation  %d %s", 401,PHP_EOL);

        $rules = [
            'nick_name' => 'required|integer',
            'visibility_status' => 'required',
        ];
        
        $data = [
            "nick_name" => "1",
            "visibility_status" => "1",
        ];
        
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }
   
    /**
     * Guest user can not access add nick name api
     */
    public function testGuesUserCanoNotAddNickName()
    {
        print sprintf("Access denied for guest user %d %s", 401,PHP_EOL);
        $parameter = [
            'nick_name' => "test",
            'visibility_status' => 1
        ];
        $response = $this->call('POST', '/api/v3/add_nick_name', $parameter);
        $this->assertEquals(401, $response->status());       
    }

    public function testAddNickNameWithInvalidData(){
        print sprintf(" \n Add Nickname %d %s", 400,PHP_EOL);
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
        print sprintf(" \n Add Nickname with valid data %d %s", 200,PHP_EOL);
        $user = User::factory()->make();

        $parameter = [
            'nick_name' => "test",
            "visibility_status" => "1"
        ];

        $this->actingAs($user)
        ->post('/api/v3/add_nick_name', $parameter);

        $this->assertEquals(200, $this->response->status());
    }

    public function testGuestUserCanNotGetAllNickNames(){
        print sprintf("\n Gues user can not get list of nicknames ",401, PHP_EOL);
        $response = $this->call('GET', '/api/v3/get_all_nicknames', []);
        $this->assertEquals(401, $response->status()); 
    }

    public function testGetAllNickNamesWithAuthorizedUser(){
        print sprintf(" \n Get all nicknames %d %s", 200,PHP_EOL);
        $user = User::factory()->make();

        $this->actingAs($user)
        ->post('/api/v3/get_all_nicknames', []);

        $this->assertEquals(200, $this->response->status());
    }


    public function testUpdateNickNameWithInvalidData(){
        print sprintf(" \n Update Nickname Visibility with invalid data %d %s", 400,PHP_EOL);
        $user = User::factory()->make();

        $parameter = [
            'nick_name_id' => "",
            "visibility_status" => ""
        ];

        $this->actingAs($user)
        ->post('/api/v3/update_nick_name', $parameter);

        $this->assertEquals(400, $this->response->status());
    }

    public function testUpdateNickNameWithValidData(){
        print sprintf(" \n Update Nickname visibilty with valid data %d %s", 200,PHP_EOL);
        $user = User::factory()->make();

        $parameter = [
            'nick_name_id' => "1",
            "visibility_status" => "1"
        ];

        $this->actingAs($user)
        ->post('/api/v3/update_nick_name', $parameter);

        $this->assertEquals(200, $this->response->status());
    }
}

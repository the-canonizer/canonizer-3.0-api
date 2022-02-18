<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class LoginApiTest extends TestCase
{

    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testLoginValidateFiled()
    {
        $rules = [
            'username' => 'required',
            'password' => 'required',
            'client_id' => 'required',
            'client_secret' => 'required',
        ];
        
        $data = [
            "username" => "email@email.com",
            "password" => "Test@123",
            "client_id" => "qwertyuio",
            "client_secret" => "qwqertyuiopasdfghjklzcvbnm",
        ];
        
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }


    public function testLoginWithInvalidData()
    {
        print sprintf(" \n Invalid Login details submitted %d %s", 400,PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)
        ->post('/api/v3/user/login',[]);    
        $this->assertEquals(400, $this->response->status());
    }


    public function testLoginWithValidaData(){
        
        print sprintf(" \n Login with valid data %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $user->username = "email@email.com";
        $user->password = "Test@123";
        $user->client_id = "qwertyuio";
        $user->client_secret = "qwqertyuiopasdfghjklzcvbnm";

        $parameters = [
            "username" => "email@email.com",
            "password" => "Test@123",
            "client_id" => "qwertyuio",
            "client_secret" => "qwqertyuiopasdfghjklzcvbnm",
        ];
        $this->actingAs($user)
        ->post('/api/v3/user/login',$parameters);
        $this->assertEquals(200, $this->response->status());
    }

}

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
            "username" => "saurabh.singh@iffort.com",
            "password" => "Test@123",
            "client_id" => "4",
            "client_secret" => "vzPs1YN0KOqImwj6TFdFt6LMekguxE1EX5xoh4A4",
        ];

        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }


    public function testLoginWithInvalidData()
    {
        print sprintf(" \n Invalid Login details submitted %d %s", 400, PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)
            ->post('/api/v3/user/login', []);
        $this->assertEquals(400, $this->response->status());
    }


    public function testLoginWithValidaData()
    {
        print sprintf(" \n Login with valid data %d %s", 200, PHP_EOL);
        $user = User::factory()->make();
        $parameters = [
            "client_id" => "2",
            "client_secret" => "5S0WQMrw3Hu6D0kfwib7Kkch86ToKJvrrzMojhuF",
            "username" => "saurabh.singh@iffort.com",
            "password" => "Test@123",
        ];
        $this->actingAs($user)->post('/api/v3/user/login', $parameters);
        //dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }
}

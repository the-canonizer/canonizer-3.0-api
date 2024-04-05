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
            "username" => "brent.allsop@canonizer.com",
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
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_1')
        ]);
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/user/login?from_test_case=1', [],$header);
            // dd($this->response);
        $this->assertEquals(400, $this->response->status());
    }


    public function testLoginWithValidData()
    {
        print sprintf(" \n Login with valid data %d %s", 200, PHP_EOL);
        $user = User::factory()->make();
        $parameters = [
            "client_id" =>  trans('testSample.user_ids.normal_user.user_3.client_id'),
            "client_secret" =>  trans('testSample.user_ids.normal_user.user_3.client_secret'),
            "username" =>  trans('testSample.user_ids.normal_user.user_3.email'),
            'password' =>  trans('testSample.user_ids.normal_user.user_3.password'),
        ];
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $this->actingAs($user)->post('/api/v3/user/login?from_test_case=1', $parameters,$header);
        //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }
}

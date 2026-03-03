<?php

namespace Tests;

use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
        $user = User::factory()->create([
            'status' => 1
        ]);
        $token = $user->createToken('TestToken')->accessToken;
        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $response = $this->actingAs($user)->post('/api/v3/user/login?from_test_case=1', [],$header);
        $response->assertStatus(400);
    }


    public function testLoginWithValidData()
    {
        print sprintf(" \n Login with valid data %d %s", 200, PHP_EOL);
        $user = User::factory()->create([
            'email' => trans('testSample.user_ids.normal_user.user_3.email'),
            'password' => bcrypt(trans('testSample.user_ids.normal_user.user_3.password')),
            'status' => 1
        ]);

        Http::fake([
            '*/oauth/token*' => Http::response([
                'access_token' => 'mock_token',
                'token_type' => 'Bearer',
                'expires_in' => 3600
            ], 200),
        ]);

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
        $response = $this->actingAs($user)->post('/api/v3/user/login?from_test_case=1', $parameters,$header);
        //  dd($response);
        $response->assertStatus(200);
    }
}

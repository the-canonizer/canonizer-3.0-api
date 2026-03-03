<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForgotPasswordSendOtpApiTest extends TestCase
{

    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testForgotPasswordSendOtpValidateFiled()
    {
        $rules = [
            'email' => 'required|string|email|max:225',
        ];
        
        $data = [
            "email" =>  trans('testSample.user_ids.normal_user.user_2.email'),
        ];
        
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testForgotPasswordSendOtpWithInvalidData(){
        print sprintf(" \n Invalid Forgot Password Send Otp details submitted %d %s", 400,PHP_EOL);

        $user = User::factory()->create();
        $token = $user->createToken('TestToken')->accessToken;

        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $parameter = [
            'email' => ""
        ];

        $response = $this->actingAs($user)
        ->post('/api/v3/forgot-password/send-otp', $parameter, $header);
        $response->assertStatus(400);
    }

    public function testForgotPasswordSendOtpWithValidData()
    {
        print sprintf(" \n Valid Forgot Password Send Otp details submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->create([
            'email' => trans('testSample.user_ids.normal_user.user_2.email'),
        ]);
        $token = $user->createToken('TestToken')->accessToken;

        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        $parameters = [
            "email" => trans('testSample.user_ids.normal_user.user_2.email'),
        ];
        $response = $this->actingAs($user)
            ->post('/api/v3/forgot-password/send-otp',$parameters , $header);   
        $response->assertStatus(200);
    }
   
}

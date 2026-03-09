<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForgotPasswordVerifyOtpApiTest extends TestCase
{

    // use DatabaseMigrations;
    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testForgotPasswordVerifyOtpWithInvalidData()
    {
        print sprintf(" \n Invalid Forgot Password details submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->create([
            'id' => trans('testSample.user_ids.normal_user.user_2.id'),
            'email' =>  trans('testSample.user_ids.normal_user.user_2.email'),
            'password' => trans('testSample.user_ids.normal_user.user_2.password'),
        ]);
        $user->otp = trans('testSample.user_ids.normal_user.user_2.otp');
        $user->save();
        $user->username = trans('testSample.user_ids.normal_user.user_2.email');

        $token = $user->createToken('TestToken')->accessToken;

        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;

        $parameters = [
            "otp" => '',
            "username" => '',
        ];

        $response = $this->actingAs($user)
            ->post('/api/v3/forgot-password/verify-otp', $parameters, $header);

        $response->assertStatus(400);
    }

    public function testForgotPasswordVerifyOtpWithValidData()
    {
        print sprintf(" \n Correct Forgot Password Otp  submitted %d %s", 200, PHP_EOL);
        $email = 'test_' . time() . '@example.com';
        $user = User::factory()->create([
            "otp" => trans('testSample.user_ids.admin_user.otp'),
            "email" => $email,
        ]);

        $token = $user->createToken('TestToken')->accessToken;

        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        
        $parameters = [
            "otp" => trans('testSample.user_ids.admin_user.otp'),
            "username" => $email
        ];

        $response = $this->actingAs($user)
            ->post('/api/v3/forgot-password/verify-otp', $parameters, $header);
        
        $response->assertStatus(200);
    }
}

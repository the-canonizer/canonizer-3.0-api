<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
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
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_2.id'),
            'email' =>  trans('testSample.user_ids.normal_user.user_2.email'),
            'password' => trans('testSample.user_ids.normal_user.user_2.password'),
        ]);
        $user->otp = trans('testSample.user_ids.normal_user.user_2.otp');
        $user->username = trans('testSample.user_ids.normal_user.user_2.email');

        $token = $user->createToken('TestToken')->accessToken;

        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;

        $parameters = [
            "otp" => '',
            "username" => '',
        ];

        $this->actingAs($user)
            ->post('/api/v3/forgot-password/verify-otp', $parameters, $header);

        $this->assertEquals(400, $this->response->status());
    }

    public function testForgotPasswordVerifyOtpWithValidData()
    {
        print sprintf(" \n Correct Forgot Password Otp  submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make([
            "otp" => trans('testSample.user_ids.admin_user.otp'),
        ]);

        $token = $user->createToken('TestToken')->accessToken;

        $header = [];
        $header['Accept'] = 'application/json';
        $header['Authorization'] = 'Bearer '.$token;
        
        $parameters = [
            "otp" => trans('testSample.user_ids.admin_user.otp'),
            "username" => trans('testSample.user_ids.admin_user.email')
        ];

        $this->actingAs($user)
            ->post('/api/v3/forgot-password/verify-otp', $parameters, $header);
        
        $this->assertEquals(200, $this->response->status());
    }
}

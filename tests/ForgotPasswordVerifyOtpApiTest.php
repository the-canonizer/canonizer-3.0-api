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

        $parameters = [
            "otp" => '',
            "username" => '',
        ];

        $this->actingAs($user)
            ->post('/api/v3/forgot-password/verify-otp', $parameters);

        $this->assertEquals(400, $this->response->status());
    }

    public function testForgotPasswordVerifyOtpWithInvalidOtp()
    {
        print sprintf(" \n Incorrect Forgot Password Otp  submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make([
            'id' => trans('testSample.user_ids.normal_user.user_2.id'),
            'email' =>  trans('testSample.user_ids.normal_user.user_2.email'),
            'password' => trans('testSample.user_ids.normal_user.user_2.password'),
        ]);

        $parameters = [
            "otp" => trans('testSample.user_ids.normal_user.user_2.otp'),
        ];

        $this->actingAs($user)
            ->post('/api/v3/forgot-password/verify-otp', $parameters);

        $this->assertEquals(400, $this->response->status());
    }


    public function testForgotPasswordVerifyOtpWithValidData()
    {
        print sprintf(" \n Correct Forgot Password Otp  submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->make([
            "otp" => trans('testSample.user_ids.normal_user.user_2.otp'),
        ]);
        //  dd($user);
        // echo gettype($user['otp']);
        $parameters = [
            "otp" => $user['otp'],
            "username" => $user['email'],
        ];
        $this->actingAs($user)
            ->post('/api/v3/forgot-password/verify-otp', $parameters);
        // dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }
}

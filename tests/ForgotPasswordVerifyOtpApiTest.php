<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForgotPasswordVerifyOtpApiTest extends TestCase
{

    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testForgotPasswordVerifyOtpWithInvalidData(){
        print sprintf(" \n Invalid Forgot Password details submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $user->otp = "123456";
        $user->username = "saurabh.singh@iffort.com";

        $parameters = [
            "otp" => '',
            "username" => '',
        ];
       
        $this->actingAs($user)
            ->post('/api/v3/forgot-password/verify-otp',$parameters);   

        $this->assertEquals(400, $this->response->status());
    }

    public function testForgotPasswordVerifyOtpWithInvalidOtp(){
        print sprintf(" \n Incorrect Forgot Password Otp  submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make();

        $parameters = [
            "otp" => '1234',
        ];
       
        $this->actingAs($user)
            ->post('/api/v3/forgot-password/verify-otp',$parameters);   

        $this->assertEquals(400, $this->response->status());
    }


    public function testForgotPasswordVerifyOtpWithValidData(){
        print sprintf(" \n Correct Forgot Password Otp  submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $parameters = [
            "otp" => '931979',
            "username" => 'saurabh.singh@iffort.com'
        ];
       
        $this->actingAs($user)
            ->post('/api/v3/forgot-password/verify-otp',$parameters);   
          //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }
}

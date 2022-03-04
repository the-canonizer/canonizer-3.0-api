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
        $user->email = "email@email.com";

        $parameters = [
            "otp" => '',
            "email" => '',
        ];
       
        $this->actingAs($user)
            ->post('/api/v3/forgotpassword/verifyOtp',$parameters);   

        $this->assertEquals(400, $this->response->status());
    }

    public function testForgotPasswordVerifyOtpWithInvalidOtp(){
        print sprintf(" \n Incorrect Forgot Password Otp  submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $user->otp = "123456";

        $parameters = [
            "otp" => '1234',
        ];
       
        $this->actingAs($user)
            ->post('/api/v3/forgotpassword/verifyOtp',$parameters);   

        $this->assertEquals(400, $this->response->status());
    }


    public function testForgotPasswordVerifyOtpWithValidData(){
        print sprintf(" \n Correct Forgot Password Otp  submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $user->otp = "123456";
        $user->email = "email@email.com";

        $parameters = [
            "otp" => '123456',
            "email" => 'email@email.com',
            
        ];
       
        $this->actingAs($user)
            ->post('/api/v3/forgotpassword/verifyOtp',$parameters);   

        $this->assertEquals(200, $this->response->status());
    }
}

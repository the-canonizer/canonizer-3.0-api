<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class ForgotPasswordVerifyOtpApiTest extends TestCase
{

    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */

    public function testForgotPasswordVerifyOtpValidateFiled()
    {
        $rules = [
            'email' => 'required|string|email|max:225|unique:person',
            'otp' => 'required',
        ];
        
        $data = [
            "email" => "email@email.com",
            "otp" => "123456",
        ];
        
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }


    public function testForgotPasswordVerifyOtpEmptyParams()
    {
        print sprintf("Invalid details submitted %d %s", 302,PHP_EOL);
        $response = $this->call('POST', '/api/v3/forgotpassword/verifyOtp', []);
        $this->assertEquals(400, $response->status());       
    }

    public function testSuccessfulForgotPasswordVerifyOtp()
    {

        print sprintf("Valid details submitted %d %s", 302,PHP_EOL);

        $parameters = [
            "email" => "email@email.com",
            "otp" => "123456",
        ];

        $this->post("api/v3/forgotpassword/verifyOtp", $parameters, []);
        $this->seeStatusCode(200);
        $this->seeJsonStructure(
            [
                "status_code" => 200,
                "message" => "Otp Verifyed",
                "error" => null,
                "data" => null
            ]
        );
    }
}

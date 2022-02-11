<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

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
            'email' => 'required|string|email|max:225|unique:person',
        ];
        
        $data = [
            "email" => "email@email.com",
        ];
        
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }


    public function testForgotPasswordSendOtpEmptyParams()
    {
        print sprintf("Invalid details submitted %d %s", 302,PHP_EOL);
        $response = $this->call('POST', '/api/v3/forgotpassword/sendOtp', []);
        $this->assertEquals(400, $response->status());       
    }

    public function testSuccessfulForgotPasswordSendOtp()
    {

        print sprintf("Valid details submitted %d %s", 302,PHP_EOL);

        $parameters = [
            "email" => "email@email.com",
        ];

        $this->post("api/v3/forgotpassword/sendOtp", $parameters, []);
        $this->seeStatusCode(200);
        $this->seeJsonStructure(
            [
                "status_code" => 200,
                "message" => "Otp sent successfully on your registered Email Id",
                "error" => null,
                "data" => null
            ]
        );
    }
}

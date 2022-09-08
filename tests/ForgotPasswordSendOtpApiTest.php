<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
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
            "email" => "saurabh.singh@iffort.com",
        ];
        
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testForgotPasswordSendOtpWithInvalidData(){
        print sprintf(" \n Invalid Forgot Password Send Otp details submitted %d %s", 400,PHP_EOL);

        $user = User::factory()->make();

        $parameter = [
            'email' => ""
        ];

        $this->actingAs($user)
        ->post('/api/v3/forgot-password/send-otp', $parameter);
        
        $this->assertEquals(400, $this->response->status());
    }

    public function testForgotPasswordSendOtpWithValidData()
    {
        print sprintf(" \n Valid Forgot Password Send Otp details submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make();

        $parameters = [
            "email" => "saurabh.singh@iffort.com"
        ];
        $this->actingAs($user)
            ->post('/api/v3/forgot-password/send-otp',$parameters);   
          //  dd($this->response);
        $this->assertEquals(200, $this->response->status());
    }
   
}

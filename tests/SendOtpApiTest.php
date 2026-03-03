<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SendOtpApiTest extends TestCase
{
    use DatabaseTransactions;

    public function testValidateSendOtpField()
    {
        print sprintf("\n Validation for send otp %d %s", 200 ,PHP_EOL);
        $rules = [
            'phone_number' => 'required|digits:10',
            'mobile_carrier' => 'required'
        ];
        
        $data = [
            "phone_number" => "9876789876",
            "mobile_carrier" => "test",
        ];
        
        $v = $this->app['validator']->make($data, $rules);
        $this->assertTrue($v->passes());
    }

    public function testSendOtpWithInvalidData()
    {
        print sprintf(" \n Invalid details submitted %d %s", 400, PHP_EOL);
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->postJson('/api/v3/send-otp', ['phone_number' => '9876567890']);  
        $response->assertStatus(400);
    }


    public function testSendOtpWithValidData()
    {
        print sprintf(" \n Valid details submitted %d %s", 200, PHP_EOL);
        $user = User::factory()->create();
        $parameters = [
            "phone_number" => "1234567890",
            "mobile_carrier" => "test"
        ];
        $response = $this->actingAs($user)
            ->postJson('/api/v3/send-otp', $parameters);   

        $response->assertStatus(200);
    }
}

<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Event;
use App\Events\SendOtpEvent;
use  Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtp;

class SendOtpApiTest extends TestCase
{

    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */

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

    public function testSendOtpWithInvalidData(){
        print sprintf(" \n Invalid details submitted %d %s", 400,PHP_EOL);
        $user = User::factory()->make();
        $this->actingAs($user)
        ->post('/api/v3/send-otp',['phone_number'=>'9876567890']);  
       // dd($_res);  
        $_res->assertStatus(400);
    }


    public function testSendOtpWithValidData()
    {
        print sprintf(" \n Valid details submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $parameters = [
            "phone_number" => "1234567890",
            "mobile_carrier" => "test"
        ];
        $this->actingAs($user)
            ->post('/api/v3/send-otp',$parameters);   

        $_res->assertStatus(200);
    }
}

<?php

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Event;
use App\Events\SendOtpEvent;
use  Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtp;

class VerifyOtpApiTest extends TestCase
{

    use DatabaseTransactions;


   public function testVerifyOtpWithInvalidData(){
        print sprintf(" \n Invalid details submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $user->otp = "123456";

        $parameters = [
            "otp" => '',
        ];
       
        $this->actingAs($user)
            ->post('/api/v3/verifyotp',$parameters);   

        $this->assertEquals(400, $this->response->status());
    }

    public function testVerifyOtpWithInvalidOtp(){
        print sprintf(" \n Incorrect Otp  submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $user->otp = "123456";

        $parameters = [
            "otp" => '1234',
        ];
       
        $this->actingAs($user)
            ->post('/api/v3/verifyotp',$parameters);   

        $this->assertEquals(400, $this->response->status());
    }

    public function testVerifyOtpWithValidData(){
        print sprintf(" \n Correct Otp  submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $user->otp = "123456";

        $parameters = [
            "otp" => '123456',
        ];
       
        $this->actingAs($user)
            ->post('/api/v3/verifyotp',$parameters);   

        $this->assertEquals(200, $this->response->status());
    }
}

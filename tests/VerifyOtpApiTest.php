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

class VerifyOtpApiTest extends TestCase
{

    use DatabaseTransactions;


   public function testVerifyOtpWithInvalidData(){
        print sprintf(" \n Invalid details submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $user->otp = "123456";

        $parameters = [
            "otp" => '',
            "username" => '',
        ];
       
        $_res = $this->actingAs($user)
            ->post('/api/v3/verify-otp',$parameters);   

        $_res->assertStatus(400);
    }

    
    public function testVerifyOtpWithInvalidOtp(){
        print sprintf(" \n Incorrect Otp  submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make();
        $user->otp = "123456";

        $parameters = [
            "otp" => '1234',
        ];
       
        $_res = $this->actingAs($user)
            ->post('/api/v3/verify-otp',$parameters);  
        $_res->assertStatus(400);
    }

    public function testVerifyOtpWithValidData(){
        print sprintf(" \n Correct Otp  submitted %d %s", 200,PHP_EOL);
        $user = User::factory()->make([
            'otp'=> '697427'
        ]);
        $parameters = [
            "otp" => '697427',
        ];
        $_res = $this->actingAs($user)->post('/api/v3/verify-otp',$parameters);  
        $_res->assertStatus(200);
    }
}

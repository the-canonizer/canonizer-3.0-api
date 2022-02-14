<?php
namespace App\Mail;
 
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class SendOtp extends Mailable {
 
    use Queueable, SerializesModels;

    public $user;
    public $settingFlag;

    public function __construct($user, $settingFlag = false)
    {
        $this->user = $user;
        $this->settingFlag = $settingFlag;
    }
    //build the message.
    public function build() {

        return $this->markdown('emails.sendOtp')->subject('One Time Verification Code');  
    }
}
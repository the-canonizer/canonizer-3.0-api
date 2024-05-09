<?php
namespace App\Mail;
 
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class EmailChange extends Mailable {
 
    use Queueable, SerializesModels;

    public $user;
    public $requestChange;

    public function __construct($user, $requestChange = false)
    {
        $this->user = $user;
        $this->requestChange = $requestChange;
    }

    //build the message.
    public function build() 
    {
        if($requestChange){
            return $this->markdown('emails.sendOtp')->subject('One Time Verification Code');  
        }else{
            return $this->markdown('emails.sendOtp')->subject('One Time Verification Code');  
        }
    }
}
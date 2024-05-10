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
    public $newEmail;

    public function __construct($user, $requestChange = false)
    {
        $this->user = $user;
        $this->requestChange = $requestChange;
    }

    //build the message.
    public function build() 
    {
        if($this->requestChange){
            return $this->markdown('emails.emailChangeRequest')->subject('Request Change of Email Address - One Time Password(OTP) Required');  
        }else{
            return $this->markdown('emails.verifyNewEmail')->subject('Verify Your new Email Address.');  
        }
    }
}
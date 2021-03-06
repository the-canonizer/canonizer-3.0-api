<?php
namespace App\Mail;
 
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class welcomeEmail extends Mailable {
 
    use Queueable, SerializesModels;
    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }
    //build the message.
    public function build() {

        return $this->markdown('emails.welcomeMail')->subject('Welcome To Canonizer');  
    }
}
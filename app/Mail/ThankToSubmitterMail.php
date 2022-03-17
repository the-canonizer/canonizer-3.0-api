<?php
namespace App\Mail;
 
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class ThankToSubmitterMail extends Mailable {
 
    use Queueable, SerializesModels;

    public $user;
    public $data;

    public function __construct($user,$data)
    {
        $this->user = $user;
        $this->data = $data;
    }
    //build the message.
    public function build() {
        return $this->markdown('emails.ThankToSubmitterMail')->subject('Thank you for contributing to Canonizer.com');  
    }
}
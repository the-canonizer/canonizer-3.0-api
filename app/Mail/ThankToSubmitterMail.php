<?php
namespace App\Mail;
 
use App\Facades\Util;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
 
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
        $subject = Util::getEmailSubjectForSandbox($this->data->namespace_id);
        return $this->markdown('emails.ThankToSubmitterMail')->subject($subject.' Thank you for contributing to Canonizer.com'); 
    }
}
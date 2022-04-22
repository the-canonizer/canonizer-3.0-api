<?php
namespace App\Mail;
 
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CampForumPostMail extends Mailable {
 
    use Queueable, SerializesModels;

    public $user;
    public $data;
    public $link;

    public function __construct($user,$link,$data)
    {
        $this->user = $user;
        $this->data = $data;
        $this->link = $link;
    }
    //build the message.
    public function build() {
        return $this->markdown('emails.CampForumPostMail')->subject('Thank you for contributing to Canonizer.com');  
    }
}
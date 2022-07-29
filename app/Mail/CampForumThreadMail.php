<?php
namespace App\Mail;
 
use App\Facades\Util;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;

class CampForumThreadMail extends Mailable {
 
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
        $subject = Util::getEmailSubjectForSandbox($this->data['namespace_id']);
        return $this->markdown('emails.CampForumThreadMail')->subject($subject.' '.$this->data['subject']); 
    }
}
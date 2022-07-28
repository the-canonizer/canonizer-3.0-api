<?php
namespace App\Mail;
 
use App\Facades\Util;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;

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
        $subject = Util::getEmailSubjectForSandbox($this->data['namespace_id']);
        return $this->markdown('emails.CampForumPostMail')->subject($subject.' '.$this->data['subject']);
    }
}
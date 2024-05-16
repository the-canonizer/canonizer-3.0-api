<?php
namespace App\Mail;
 
use App\Models\User;
use App\Facades\Util;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class NotifyDelegatorAndDelegatedMail extends Mailable {
 
    use Queueable, SerializesModels;

    public $user;
    public $data;

    public function __construct($user, $data)
    {
        $this->user = $user;
        $this->data = $data;
    }

    //build the message.
    public function build() 
    {
        $subject = Util::getEmailSubjectForSandbox($this->data['namespace_id']);
        return $this->markdown('emails.notifyDelegatorAndDelegatedMail')->subject($subject.' '.$this->data['subject']);  
    }
}
<?php
namespace App\Mail;
 
use App\Models\User;
use App\Facades\Util;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class PromotedDelegatesMail extends Mailable {
 
    use SerializesModels;

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
        if(isset($this->data['delegate_nick_name_id']) && !empty($this->data['delegate_nick_name_id'])){
            return $this->markdown('emails.promotedDelegatesOneLevelUp')->subject($subject.' '.$this->data['subject']);  
        }else
            return $this->markdown('emails.promotedDelegatesToDirectMail')->subject($subject.' '.$this->data['subject']);  
    }
}
<?php
namespace App\Mail;
 
use App\Models\User;
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
        if(isset($data['delegate_nick_name_id']) && !empty($data['delegate_nick_name_id'])){
            return $this->markdown('emails.promotedDelegatesOneLevelUp')->subject($data['subject']);  
        }else
            return $this->markdown('emails.promotedDelegatesToDirectMail')->subject($data['subject']);  
    }
}
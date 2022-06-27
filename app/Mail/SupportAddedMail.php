<?php
namespace App\Mail;
 
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
 
class SupportAddedMail extends Mailable {
 
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
        return $this->markdown('emails.supportAddedMail')->subject($this->data['subject']);  
    }
}
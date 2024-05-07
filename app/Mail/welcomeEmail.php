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
    public $link_index_page;

    public function __construct($user,$link_index_page)
    {
        $this->user = $user;
        $this->link_index_page = $link_index_page;
    }
    //build the message.
    public function build() {

        return $this->markdown('emails.welcomeMail')->subject('Welcome To Canonizer');  
    }
}
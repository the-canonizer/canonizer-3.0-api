<?php
namespace App\Mail;
 
use App\Facades\Util;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;

class NotifyAdministratorMail extends Mailable {
 
    use Queueable, SerializesModels;

    public $url;
    public $refererURL;

    public function __construct($url, $refererURL)
    {
        $this->url = $url;
        $this->refererURL = $refererURL;
    }
    //build the message.
    public function build() {
        return $this->markdown('emails.NotifyAdministratorMail')->subject('Invalid Urls');
    }
}
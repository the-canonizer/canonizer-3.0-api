<?php

namespace App\Mail;

use App\Models\User;
use App\Facades\Util;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ObjectionToSubmitterMail extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    public $link;
    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $link,$data)
    {
        $this->user = $user;
        $this->link = $link;
	    $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = Util::getEmailSubjectForSandbox($this->data['namespace_id']);
        return  $this->markdown('emails.objectionToSubmitter')->subject($subject.' '.$this->data['subject']);
    }
}

<?php

namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Mail\ObjectionToSubmitterMail;
use Illuminate\Support\Facades\Mail;

class ObjectionToSubmitterMailJob extends Job
{
    use InteractsWithQueue, Queueable, SerializesModels;
    private $data;
    private $user;
    private $link;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $link, $data)
    {
        $this->user = $user;
        $this->link = $link;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $receiver =  $this->user->email;
        Mail::to($receiver)->bcc(env('ADMIN_BCC'))->send(new ObjectionToSubmitterMail($this->user, $this->link, $this->data));
    }
}

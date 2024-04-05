<?php

namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Mail\PurposedToSupportersMail;
use Illuminate\Support\Facades\Mail;


class PurposedToSupportersMailJob extends Job
{
    use InteractsWithQueue, Queueable, SerializesModels;
    private $data;
    private $user;
    private $link;
    private $receiver;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $link, $data, $receiver)
    {
        $this->user = $user;
        $this->link = $link;
        $this->data = $data;
        $this->receiver = $receiver;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->receiver)->bcc(env('ADMIN_BCC'))->send(new PurposedToSupportersMail($this->user, $this->link, $this->data));
    }
}

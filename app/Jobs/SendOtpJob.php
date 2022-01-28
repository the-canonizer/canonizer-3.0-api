<?php

namespace App\Jobs;

use App\Mail\sendOtp;
use Illuminate\Support\Facades\Mail;

class SendOtpJob extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $user;
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = $this->user;
        Mail::to($user->email)->bcc(config('app.admin_bcc'))->send(new sendOtp($user));
    }
}

<?php

namespace App\Listeners;

use App\Events\ForgotPasswordSendOtpEvent;
use App\Helpers\PostmarkMailer;
use Illuminate\Support\Facades\Log;

class ForgotPasswordSendOtpListener
{
    public function __construct() {}

    public function handle($event)
    {
        $user = $event->user;

        $name = htmlspecialchars($user->first_name . ' ' . $user->last_name);
        $otp  = htmlspecialchars($user->otp);

        $html = '<html><body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">'
              . '<p>Hello ' . $name . ',</p>'
              . '<p>Your one-time verification code for password reset is:</p>'
              . '<p style="font-size:32px;font-weight:bold;color:#497BDF;letter-spacing:4px;">' . $otp . '</p>'
              . '<p>If you have any issues, email <a href="mailto:support@canonizer.com">support@canonizer.com</a></p>'
              . '<p>Sincerely,<br><span style="color:#497BDF;">The Canonizer Team</span></p>'
              . '</body></html>';

        $sent = PostmarkMailer::send($user->email, 'One Time Verification Code', $html);

        if (!$sent) {
            Log::error('PostmarkMailer failed to send forgot-password OTP to ' . $user->email);
        }
    }
}

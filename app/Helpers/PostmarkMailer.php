<?php

namespace App\Helpers;

class PostmarkMailer
{
    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        $token = env('POSTMARK_API_TOKEN');
        $from  = env('MAIL_FROM_ADDRESS', 'jim@canonizer.com');

        $payload = json_encode([
            'From'     => $from,
            'To'       => $to,
            'Subject'  => $subject,
            'HtmlBody' => $htmlBody,
        ]);

        $ch = curl_init('https://api.postmarkapp.com/email');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
                'X-Postmark-Server-Token: ' . $token,
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }
}

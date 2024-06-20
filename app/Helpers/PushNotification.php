<?php

namespace App\Helpers;

use App\Models\PushNotification as ModelPushNotification;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class PushNotification
{
    public static function sendPushNotification($request)
    {
        $saveNotificationData = self::savePushNotification($request);
        if (empty($request->fcm_token)) {
            return true;
        }

        $user = User::find($request->user_id);
        $token = $user->userOAuthTokenForFCM();
        $fcmUrl = 'https://fcm.googleapis.com/v1/projects/' . env('FCM_PROJECT_ID') . '/messages:send';

        $queryString = parse_url($request->link, PHP_URL_QUERY);
        $link = $request->link . (empty($queryString) ? '?' : '&') . 'from=notify_' . $saveNotificationData->id;

        $payload = [
            'message' => [
                'token' => $request->fcm_token,
                'notification' => [
                    'title' => $request->title,
                    'body' => $request->message_body,
                ],
                'data' => [
                    'url' => $link,
                ]
            ]
        ];

        $result = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ])->post($fcmUrl, $payload);

        ModelPushNotification::where('id', $saveNotificationData->id)->update(['message_response' => $result->json()]);
        return $result;
    }

    public static function savePushNotification($request)
    {
        return ModelPushNotification::create([
            "user_id" => $request->user_id,
            "topic_num" => $request->topic_num,
            "camp_num" => $request->camp_num,
            "notification_type" => $request->notification_type,
            "message_title" => $request->title,
            "message_body" => $request->message_body,
            "fcm_token" => $request->fcm_token,
            "thread_id" => $request->thread_id ?? null,
        ]);
    }
}

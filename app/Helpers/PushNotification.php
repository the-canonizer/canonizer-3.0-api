<?php

namespace App\Helpers;

use App\Models\PushNotification as ModelPushNotification;

class PushNotification
{


    public static function sendPushNotification($request)
    {

        $saveNotificationData = self::savePushNotification($request);
        if (empty($request->fcm_token)) {
            return true;
        }
        $url = env('FCM_URL');
        $serverKey = env('FCM_SERVER_KEY');
        $fcmToken[] = $request->fcm_token;
        $data = [
            "registration_ids" => $fcmToken,
            "notification" => [
                "title" => $request->title,
                "body" => $request->message_body,
                "url" => $request->link,
            ]
        ];
        $encodedData = json_encode($data);

        $headers = [
            'Authorization:key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);

        // Execute post
        $result = curl_exec($ch);

        if ($result === FALSE) {
            return null;
        }

        // Close connection
        curl_close($ch);
        ModelPushNotification::where('id', $saveNotificationData->id)->update(['message_response' => $result]);
        return $result;
    }

    public static function savePushNotification($request)
    {

        $pushNotification = ModelPushNotification::create([
            "user_id" => $request->user_id,
            "topic_num" => $request->topic_num,
            "camp_num" => $request->camp_num,
            "notification_type" => $request->notification_type,
            "message_title" => $request->title,
            "message_body" => $request->message_body,
            "fcm_token" => $request->fcm_token,
            "thread_id" => $request->thread_id ?? NULL,
        ]);

        return $pushNotification;
    }
}

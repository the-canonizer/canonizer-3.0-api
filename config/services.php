<?php

return [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_CALLBACK_URL'),
    ],
    'facebook' => [
        'client_id'     => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect'      => env('FACEBOOK_URL'),
    ],
    'twitter' => [
        'client_id'     => env('TWITTER_CLIENT_ID'),
        'client_secret' => env('TWITTER_CLIENT_SECRET'),
        'redirect'      => env('TWITTER_URL'),
    ],
    'github' => [
        'client_id' => env ('GITHUB_CLIENT_ID'),
        'client_secret' => env ('GITHUB_CLIENT_SECRET'),
        'redirect' => env ('GITHUB_REDIRECT'),
    ],
    'linkedin' => [
        'client_id' => env ('LINKEDIN_CLIENT_ID'),
        'client_secret' => env ('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env ('LINKEDIN_REDIRECT')
    ],
];
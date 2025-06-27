<?php
return [
    'google' => [
        'client_id' => env('SSO_GOOGLE_CLIENT_ID'),
        'client_secret' => env('SSO_GOOGLE_CLIENT_SECRET'),
        'redirect' => env('SSO_GOOGLE_REDIRECT'),
    ],
    'github' => [
        'client_id' => env('SSO_GITHUB_CLIENT_ID'),
        'client_secret' => env('SSO_GITHUB_CLIENT_SECRET'),
        'redirect' => env('SSO_GITHUB_REDIRECT'),
    ],
    'twitter' => [
        'client_id' => env('SSO_TWITTER_CLIENT_ID'),
        'client_secret' => env('SSO_TWITTER_CLIENT_SECRET'),
        'redirect' => env('SSO_TWITTER_REDIRECT'),
    ],
    'linkedin' => [
        'client_id' => env('SSO_LINKEDIN_CLIENT_ID'),
        'client_secret' => env('SSO_LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('SSO_LINKEDIN_REDIRECT'),
    ],
    'jumpcloud' => [
        'client_id' => env('SSO_JUMPCLOUD_CLIENT_ID'),
        'client_secret' => env('SSO_JUMPCLOUD_CLIENT_SECRET'),
        'redirect' => env('SSO_JUMPCLOUD_REDIRECT'),
    ],
    'aws' => [
        'region' => env('SSO_AWS_COGNITO_REGION'),
        'client_id' => env('SSO_AWS_COGNITO_CLIENT_ID'),
        'client_secret' => env('SSO_AWS_COGNITO_CLIENT_SECRET'),
        'user_pool_id' => env('SSO_AWS_COGNITO_USER_POOL_ID'),
    ],
];

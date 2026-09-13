<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Which engine UploadImageAction dispatches to:
    | 'standard' - CPU compositing pipeline (ai-service/standard, STANDARD_AI_SERVICE_URL)
    | 'premium'  - Modal-hosted generative engine (AI_SERVICE_URL, ProcessImageJob)
    */
    'standard_ai' => [
        'url' => env('STANDARD_AI_SERVICE_URL', 'http://127.0.0.1:8002'),
    ],

    'premium_ai' => [
        'url' => env('AI_SERVICE_URL'),
    ],

    'image_processing' => [
        'mode' => env('IMAGE_PROCESSING_MODE', 'standard'),

        // welcome.blade.php background preset (data-prompt) -> Standard template_id.
        // Unmapped presets fall back to the API's own default (T01_warm_ivory).
        'standard_templates' => [
            'izakaya' => 'T05_japanese_editorial',
            'cafe' => 'T02_cool_white',
            'ramen' => 'T04_dark_premium',
        ],
    ],

];

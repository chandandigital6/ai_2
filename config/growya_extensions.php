<?php

return [
    'otp' => ['driver' => env('GROWYA_OTP_DRIVER', 'disabled'), 'url' => env('GROWYA_SMS_URL'), 'token' => env('GROWYA_SMS_TOKEN'), 'sender' => env('GROWYA_SMS_SENDER'), 'template_id' => env('GROWYA_SMS_TEMPLATE_ID')],
    'fcm' => [
        'enabled' => env('GROWYA_FCM_ENABLED', false),
        'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS') ?: storage_path('app/private/firebase-service-account.json'),
        'web' => [
            'enabled' => env('GROWYA_FCM_WEB_ENABLED', false),
            'api_key' => env('FIREBASE_WEB_API_KEY'),
            'auth_domain' => env('FIREBASE_WEB_AUTH_DOMAIN'),
            'project_id' => env('FIREBASE_WEB_PROJECT_ID'),
            'sender_id' => env('FIREBASE_WEB_MESSAGING_SENDER_ID'),
            'app_id' => env('FIREBASE_WEB_APP_ID'),
            'vapid_key' => env('FIREBASE_WEB_VAPID_KEY'),
        ],
    ],
    'ai' => ['enabled' => env('GROWYA_AI_ENABLED', false), 'key' => env('OPENAI_API_KEY'), 'text_model' => env('GROWYA_AI_TEXT_MODEL', 'gpt-4.1-mini'), 'image_model' => env('GROWYA_AI_IMAGE_MODEL', 'gpt-image-1'), 'text_credits' => 1, 'image_credits' => 10],
    'meta' => ['app_id' => env('META_APP_ID'), 'secret' => env('META_APP_SECRET'), 'version' => env('META_GRAPH_VERSION'), 'redirect_uri' => env('META_REDIRECT_URI')],
    'razorpay' => ['enabled' => env('RAZORPAY_ENABLED', false), 'key' => env('RAZORPAY_KEY_ID'), 'secret' => env('RAZORPAY_KEY_SECRET'), 'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET')],
];
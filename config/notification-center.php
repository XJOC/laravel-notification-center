<?php

declare(strict_types=1);

return [
    'admin_middleware' => ['auth:sanctum', 'role:admin'],
    'user_middleware' => ['auth:sanctum'],
    'route_prefix' => 'notification-center',

    // String class names (NOT ::class) so the package never autoloads the host's
    // models at config-parse time.
    'user_model' => 'App\\Models\\User',

    // Models permitted as dispatch recipients (allowlist). Add your notifiables.
    'notifiable_models' => [
        'App\\Models\\User',
    ],

    'channels' => ['mail', 'database', 'whatsapp'],

    'cache' => [
        'enabled' => true,
        'store' => null, // null = default cache store
        'ttl' => 3600,
        'prefix' => 'notification-center',
    ],

    'templates' => [
        'escape_html' => true,        // escape variable VALUES in html channels
        'html_channels' => ['mail'],    // channels treated as HTML
        'on_missing_var' => 'empty',     // 'empty' | 'throw'
    ],

    // Tier-1 coded types synced to DB via notification-center:sync.
    'types' => [
        'order.confirmed' => [
            'name' => 'Order Confirmed',
            'category' => 'transactional',
            'channels' => ['mail', 'whatsapp'],
            'locked' => false,
            'variables' => ['customer_name', 'order_id', 'total'],
        ],
        'otp.sent' => [
            'name' => 'OTP Sent',
            'category' => 'essential',
            'channels' => ['whatsapp'],
            'locked' => true,
            'variables' => ['otp_code', 'expires_in'],
        ],
    ],
];

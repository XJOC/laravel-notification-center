<?php

declare(strict_types=1);
use Xjoc\NotificationCenter\Channels\DatabaseChannel;
use Xjoc\NotificationCenter\Channels\MailChannel;
use Xjoc\NotificationCenter\Channels\WhatsappChannel;

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

    // Registered channel drivers, keyed by channel name. Each driver implements
    // Xjoc\NotificationCenter\Contracts\NotificationChannel and renders its own
    // template format. This map is the authoritative list of channels an admin
    // may assign to a notification type. Add custom channels here (or register
    // them on Xjoc\NotificationCenter\Channels\ChannelRegistry in a provider).
    'channels' => [
        'mail' => MailChannel::class,
        'database' => DatabaseChannel::class,
        'whatsapp' => WhatsappChannel::class,
    ],

    'cache' => [
        'enabled' => true,
        'store' => null, // null = default cache store
        'ttl' => 3600,
        'prefix' => 'notification-center',
    ],

    'templates' => [
        // Whether the mail driver escapes variable VALUES (HTML). Other drivers
        // decide their own escaping; the mail driver honors this flag.
        'escape_html' => true,
        'on_missing_var' => 'empty',     // 'empty' | 'throw'
    ],

    'whatsapp' => [
        // Your implementation of Xjoc\NotificationCenter\Contracts\WhatsappTransport
        // (FQCN), or bind the interface in a service provider. The package ships
        // NO provider integration; until you configure a transport, WhatsApp
        // delivery throws a clear exception.
        'transport' => null,
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

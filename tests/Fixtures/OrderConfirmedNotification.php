<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Tests\Fixtures;

use Illuminate\Notifications\Notification;
use Xjoc\NotificationCenter\Concerns\HasNotificationCenter;
use Xjoc\NotificationCenter\Contracts\NotifiableNotification;

/**
 * A typical low-touch host notification: it declares its type + variables and
 * relies entirely on injected templates (built by the gateway listener) for the
 * channel payloads.
 */
final class OrderConfirmedNotification extends Notification implements NotifiableNotification
{
    use HasNotificationCenter;

    public function notificationType(): string
    {
        return 'order.confirmed';
    }

    /**
     * @return array<string, mixed>
     */
    public function notificationVariables(object $notifiable): array
    {
        return [
            'customer_name' => 'Sam',
            'order_id' => '42',
            'total' => '$10',
        ];
    }
}

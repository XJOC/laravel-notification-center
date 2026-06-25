<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Tests\Fixtures;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use XJOC\NotificationCenter\Concerns\HasNotificationCenter;
use XJOC\NotificationCenter\Contracts\NotifiableNotification;

/**
 * Proves the "developer wins" rule: when a host overrides toMail(), their method
 * takes precedence over the trait's injected-template behaviour (PHP method
 * resolution shadows the trait method).
 */
final class CustomMailNotification extends Notification implements NotifiableNotification
{
    use HasNotificationCenter;

    public const CUSTOM_SUBJECT = 'Custom Subject';

    public const CUSTOM_LINE = 'Hand-written mail body.';

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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(self::CUSTOM_SUBJECT)
            ->line(self::CUSTOM_LINE);
    }
}

<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Notifications;

use Illuminate\Notifications\Notification;
use XJOC\NotificationCenter\Concerns\HasNotificationCenter;
use XJOC\NotificationCenter\Contracts\NotifiableNotification;
use XJOC\NotificationCenter\Support\NotificationCenterCache;

final class GenericNotification extends Notification implements NotifiableNotification
{
    use HasNotificationCenter;

    /**
     * @param  array<string, mixed>  $variables
     * @param  array<int, string>|null  $channels
     */
    public function __construct(
        private string $typeKey,
        private array $variables = [],
        private ?array $channels = null,
    ) {}

    public function notificationType(): string
    {
        return $this->typeKey;
    }

    /** @return array<string, mixed> */
    public function notificationVariables(object $notifiable): array
    {
        return $this->variables;
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return $this->channels ?? app(NotificationCenterCache::class)->supportedChannels($this->typeKey);
    }
}

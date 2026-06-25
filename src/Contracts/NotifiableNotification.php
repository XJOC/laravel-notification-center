<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Contracts;

interface NotifiableNotification
{
    public function notificationType(): string;

    /**
     * @return array<string, mixed>
     */
    public function notificationVariables(object $notifiable): array;

    public function injectTemplate(string $channel, string $rendered, ?string $subject = null): void;
}

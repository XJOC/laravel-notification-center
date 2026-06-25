<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Contracts;

interface ProvidesNotificationContext
{
    /**
     * @return iterable<int, object>
     */
    public function notificationRecipients(): iterable;

    /**
     * @return array<string, mixed>
     */
    public function notificationVariables(): array;
}

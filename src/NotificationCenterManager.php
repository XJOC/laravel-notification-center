<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter;

use Illuminate\Contracts\Notifications\Dispatcher;
use XJOC\NotificationCenter\Notifications\GenericNotification;

final class NotificationCenterManager
{
    public function __construct(private Dispatcher $dispatcher) {}

    /**
     * @param  iterable<int, object>|object  $notifiables
     * @param  array<string, mixed>  $variables
     * @param  array<int, string>|null  $channels
     */
    public function send(string $typeKey, iterable|object $notifiables, array $variables = [], ?array $channels = null): void
    {
        $notification = new GenericNotification($typeKey, $variables, $channels);

        $this->dispatcher->send($notifiables, $notification);
    }
}

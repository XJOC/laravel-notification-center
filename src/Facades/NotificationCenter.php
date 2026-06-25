<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Facades;

use Illuminate\Support\Facades\Facade;
use XJOC\NotificationCenter\NotificationCenterManager;

/**
 * @method static void send(string $typeKey, iterable<int, object>|object $notifiables, array<string, mixed> $variables = [], array<int, string>|null $channels = null)
 *
 * @see NotificationCenterManager
 */
final class NotificationCenter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'notification-center';
    }
}

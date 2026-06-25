<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Exceptions;

use RuntimeException;

final class UnregisteredChannelException extends RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self(
            "No channel driver is registered for [{$key}]. Register it via the "
            .'notification-center.channels config or a service provider.'
        );
    }
}

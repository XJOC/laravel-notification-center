<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Exceptions;

use RuntimeException;

final class MissingTemplateException extends RuntimeException
{
    public static function forChannel(string $type, string $channel): self
    {
        return new self(
            "No template injected for notification type [{$type}] on channel [{$channel}] and no override was provided."
        );
    }
}

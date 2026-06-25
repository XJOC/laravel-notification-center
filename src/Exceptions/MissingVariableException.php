<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Exceptions;

use RuntimeException;

final class MissingVariableException extends RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self("Missing template variable [{$key}].");
    }
}

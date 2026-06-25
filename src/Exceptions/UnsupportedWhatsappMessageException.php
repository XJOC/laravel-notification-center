<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Exceptions;

use RuntimeException;

final class UnsupportedWhatsappMessageException extends RuntimeException
{
    public static function forKind(string $kind): self
    {
        return new self("WhatsApp [{$kind}] messages are not supported yet.");
    }
}

<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Exceptions;

use RuntimeException;
use XJOC\NotificationCenter\Contracts\WhatsappTransport;

final class MissingWhatsappTransportException extends RuntimeException
{
    public static function make(): self
    {
        return new self(
            'No WhatsApp transport is configured. Register your own implementation of '
            .WhatsappTransport::class.' via the notification-center.whatsapp.transport config '
            .'or by binding the interface in a service provider.'
        );
    }
}

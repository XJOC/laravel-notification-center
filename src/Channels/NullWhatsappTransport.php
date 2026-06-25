<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Channels;

use XJOC\NotificationCenter\Contracts\WhatsappTransport;
use XJOC\NotificationCenter\Exceptions\MissingWhatsappTransportException;

/**
 * The default WhatsApp transport. It ships zero provider integration: any
 * attempt to deliver throws a clear exception telling the developer to register
 * their own WhatsappTransport.
 */
final class NullWhatsappTransport implements WhatsappTransport
{
    public function send(WhatsappMessage $message): void
    {
        throw MissingWhatsappTransportException::make();
    }
}

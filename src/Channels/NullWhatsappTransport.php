<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Channels;

use Xjoc\NotificationCenter\Contracts\WhatsappTransport;
use Xjoc\NotificationCenter\Exceptions\MissingWhatsappTransportException;

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

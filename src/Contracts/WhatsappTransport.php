<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Contracts;

use XJOC\NotificationCenter\Channels\WhatsappMessage;

/**
 * Delivery contract for WhatsApp. The package renders the template and builds a
 * structured WhatsappMessage; the developer implements ONLY delivery by mapping
 * the message to their provider's API (Twilio, Meta Cloud API, etc.). The
 * package ships no provider integration.
 *
 * The single, stable method takes a structured message object (not a flat
 * string) so new message kinds can be added to WhatsappMessage without changing
 * this contract.
 */
interface WhatsappTransport
{
    public function send(WhatsappMessage $message): void;
}

<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Tests\Fixtures;

use XJOC\NotificationCenter\Channels\WhatsappMessage;
use XJOC\NotificationCenter\Contracts\WhatsappTransport;

/**
 * A test transport that captures the structured WhatsappMessage instead of
 * delivering it, so tests can assert what the channel handed off.
 */
final class FakeWhatsappTransport implements WhatsappTransport
{
    /** @var array<int, WhatsappMessage> */
    public array $messages = [];

    public function send(WhatsappMessage $message): void
    {
        $this->messages[] = $message;
    }
}

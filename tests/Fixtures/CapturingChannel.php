<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Tests\Fixtures;

use Illuminate\Notifications\Notification;
use Xjoc\NotificationCenter\Enums\Channel;

/**
 * A test channel driver registered via ChannelManager->extend(). Instead of
 * delivering anywhere, it builds the channel payload from the notification's
 * per-channel method and records it on the NotificationSpy so tests can assert
 * what would have been delivered after the gateway + template injection ran.
 */
final class CapturingChannel
{
    public function __construct(private string $name) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $payload = $this->payloadFor($notifiable, $notification);

        NotificationSpy::record($this->name, $notifiable, $payload);
    }

    private function payloadFor(object $notifiable, Notification $notification): mixed
    {
        return match ($this->name) {
            Channel::Mail->value => $notification->toMail($notifiable), // @phpstan-ignore-line method.notFound
            Channel::Database->value => $notification->toDatabase($notifiable), // @phpstan-ignore-line method.notFound
            Channel::Whatsapp->value => $notification->toWhatsapp($notifiable), // @phpstan-ignore-line method.notFound
            default => null,
        };
    }
}

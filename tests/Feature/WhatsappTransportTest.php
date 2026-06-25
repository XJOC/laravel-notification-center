<?php

declare(strict_types=1);

use Illuminate\Notifications\Notification;
use Xjoc\NotificationCenter\Channels\WhatsappChannel;
use Xjoc\NotificationCenter\Channels\WhatsappMessage;
use Xjoc\NotificationCenter\Concerns\HasNotificationCenter;
use Xjoc\NotificationCenter\Contracts\NotifiableNotification;
use Xjoc\NotificationCenter\Contracts\WhatsappTransport;
use Xjoc\NotificationCenter\Exceptions\MissingWhatsappTransportException;
use Xjoc\NotificationCenter\Exceptions\UnsupportedWhatsappMessageException;
use Xjoc\NotificationCenter\Tests\Fixtures\FakeWhatsappTransport;
use Xjoc\NotificationCenter\Tests\Fixtures\OrderConfirmedNotification;
use Xjoc\NotificationCenter\Tests\Fixtures\User;

it('renders to text and delegates delivery to the bound transport', function (): void {
    $fake = new FakeWhatsappTransport;
    app()->instance(WhatsappTransport::class, $fake);

    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    $notification = new OrderConfirmedNotification;
    $notification->injectTemplate('whatsapp', 'Hi {{ customer_name }}, order {{ order_id }}.');

    app(WhatsappChannel::class)->send($user, $notification);

    expect($fake->messages)->toHaveCount(1);

    $message = $fake->messages[0];
    expect($message->type)->toBe('text')
        ->and($message->isText())->toBeTrue()
        ->and($message->to)->toBe('+15555550123')
        ->and($message->body)->toBe('Hi Sam, order 42.');
});

it('keeps the whatsapp body raw (no HTML escaping) through the transport', function (): void {
    $fake = new FakeWhatsappTransport;
    app()->instance(WhatsappTransport::class, $fake);

    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    $notification = new class extends Notification implements NotifiableNotification
    {
        use HasNotificationCenter;

        public function notificationType(): string
        {
            return 'order.confirmed';
        }

        /** @return array<string, mixed> */
        public function notificationVariables(object $notifiable): array
        {
            return ['value' => '<b>x</b>'];
        }
    };
    $notification->injectTemplate('whatsapp', 'Body {{ value }}');

    app(WhatsappChannel::class)->send($user, $notification);

    expect($fake->messages[0]->body)->toBe('Body <b>x</b>');
});

it('throws a clear exception when no transport is configured', function (): void {
    // No transport bound -> the package default NullWhatsappTransport is active.
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    $notification = new OrderConfirmedNotification;
    $notification->injectTemplate('whatsapp', 'Hi {{ customer_name }}');

    expect(function () use ($user, $notification): void {
        app(WhatsappChannel::class)->send($user, $notification);
    })->toThrow(MissingWhatsappTransportException::class);
});

it('builds a text message via the text() factory', function (): void {
    $message = WhatsappMessage::text('+15555550123', 'Hello');

    expect($message->type)->toBe(WhatsappMessage::TYPE_TEXT)
        ->and($message->isText())->toBeTrue()
        ->and($message->to)->toBe('+15555550123')
        ->and($message->body)->toBe('Hello');
});

it('reserves future message kinds with a clear not-supported-yet exception', function (): void {
    expect(fn (): WhatsappMessage => WhatsappMessage::file('+15555550123', 'https://example.test/x.pdf'))
        ->toThrow(UnsupportedWhatsappMessageException::class);

    expect(fn (): WhatsappMessage => WhatsappMessage::location('+15555550123', 1.0, 2.0))
        ->toThrow(UnsupportedWhatsappMessageException::class);

    expect(fn (): WhatsappMessage => WhatsappMessage::buttons('+15555550123', 'Pick one', []))
        ->toThrow(UnsupportedWhatsappMessageException::class);
});

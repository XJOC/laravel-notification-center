<?php

declare(strict_types=1);

use Xjoc\NotificationCenter\Channels\ChannelRegistry;
use Xjoc\NotificationCenter\Channels\MailChannel;
use Xjoc\NotificationCenter\Channels\WhatsappChannel;
use Xjoc\NotificationCenter\Contracts\NotificationChannel;
use Xjoc\NotificationCenter\Exceptions\UnregisteredChannelException;
use Xjoc\NotificationCenter\Tests\Fixtures\FakeChannel;

it('resolves a registered driver by key', function (): void {
    $registry = app(ChannelRegistry::class);

    expect($registry->driver('mail'))->toBeInstanceOf(MailChannel::class)
        ->and($registry->driver('mail')->key())->toBe('mail')
        ->and($registry->driver('whatsapp'))->toBeInstanceOf(WhatsappChannel::class);
});

it('exposes the registered keys as the admin-pickable channel list', function (): void {
    expect(app(ChannelRegistry::class)->keys())
        ->toEqualCanonicalizing(['mail', 'database', 'whatsapp']);
});

it('answers has() for registered and unregistered keys', function (): void {
    $registry = app(ChannelRegistry::class);

    expect($registry->has('mail'))->toBeTrue()
        ->and($registry->has('telegram'))->toBeFalse();
});

it('throws a clear exception for an unregistered channel', function (): void {
    expect(fn (): NotificationChannel => app(ChannelRegistry::class)->driver('telegram'))
        ->toThrow(UnregisteredChannelException::class);
});

it('registers a custom driver via class-string and resolves it', function (): void {
    $registry = app(ChannelRegistry::class);
    $registry->register('fake', FakeChannel::class);

    expect($registry->has('fake'))->toBeTrue()
        ->and($registry->driver('fake'))->toBeInstanceOf(FakeChannel::class)
        ->and($registry->keys())->toContain('fake');
});

it('rejects a registered class string that does not resolve to a driver', function (): void {
    $registry = app(ChannelRegistry::class);
    $registry->register('bad', stdClass::class);

    expect($registry->has('bad'))->toBeTrue()
        ->and(fn (): NotificationChannel => $registry->driver('bad'))
        ->toThrow(UnregisteredChannelException::class);
});

it('registers a custom driver instance and returns the same instance', function (): void {
    $registry = app(ChannelRegistry::class);
    $instance = new FakeChannel;
    $registry->register('fake', $instance);

    expect($registry->driver('fake'))->toBe($instance);
});

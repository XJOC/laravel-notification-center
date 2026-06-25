<?php

declare(strict_types=1);

use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use XJOC\NotificationCenter\Facades\NotificationCenter;
use XJOC\NotificationCenter\Listeners\NotificationGatewayListener;
use XJOC\NotificationCenter\NotificationCenterManager;
use XJOC\NotificationCenter\Support\NotificationCenterCache;
use XJOC\NotificationCenter\Support\PreferenceResolver;
use XJOC\NotificationCenter\Support\RecipientResolver;
use XJOC\NotificationCenter\Templates\TemplateRenderer;

it('resolves the notification-center binding to the manager', function (): void {
    $resolved = app()->make('notification-center');

    expect($resolved instanceof NotificationCenterManager)->toBeTrue();
});

it('resolves the facade root to the manager', function (): void {
    expect(NotificationCenter::getFacadeRoot())->toBeInstanceOf(NotificationCenterManager::class);
});

it('binds the manager and cache as singletons', function (): void {
    expect(app(NotificationCenterManager::class))
        ->toBe(app(NotificationCenterManager::class));

    expect(app(NotificationCenterCache::class))
        ->toBe(app(NotificationCenterCache::class));
});

it('can resolve the support services from the container', function (): void {
    expect(app(NotificationCenterCache::class))->toBeInstanceOf(NotificationCenterCache::class)
        ->and(app(PreferenceResolver::class))->toBeInstanceOf(PreferenceResolver::class)
        ->and(app(RecipientResolver::class))->toBeInstanceOf(RecipientResolver::class)
        ->and(app(TemplateRenderer::class))->toBeInstanceOf(TemplateRenderer::class);
});

it('registers the gateway listener for the NotificationSending event', function (): void {
    expect(Event::hasListeners(NotificationSending::class))->toBeTrue();

    /** @var array<int, mixed> $listeners */
    $listeners = Event::getRawListeners()[NotificationSending::class] ?? [];

    $matches = collect($listeners)->contains(function (mixed $listener): bool {
        if (is_array($listener)) {
            return ($listener[0] ?? null) === NotificationGatewayListener::class;
        }

        return is_string($listener) && str_contains($listener, NotificationGatewayListener::class);
    });

    expect($matches)->toBeTrue();
});

it('merges the package configuration', function (): void {
    expect(config('notification-center'))->toBeArray()
        ->and(config('notification-center.route_prefix'))->toBe('notification-center')
        ->and(config('notification-center.channels'))->toBeArray();
});

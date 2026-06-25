<?php

declare(strict_types=1);

use Xjoc\NotificationCenter\NotificationCenterServiceProvider;

it('boots the application with the package registered', function (): void {
    expect(app()->getProvider(NotificationCenterServiceProvider::class))
        ->toBeInstanceOf(NotificationCenterServiceProvider::class);
});

it('merges the package configuration', function (): void {
    expect(config('notification-center'))->toBeArray();
});

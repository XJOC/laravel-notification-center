<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Xjoc\NotificationCenter\Models\NotificationSetting;
use Xjoc\NotificationCenter\Models\NotificationType;
use Xjoc\NotificationCenter\Support\NotificationCenterCache;

function cachingMakeType(bool $settingEnabled = true): NotificationType
{
    /** @var NotificationType $type */
    $type = NotificationType::query()->create([
        'key' => 'order.confirmed',
        'name' => 'Order Confirmed',
        'category' => 'transactional',
        'supported_channels' => ['whatsapp'],
        'variables' => ['order_id'],
        'is_locked' => false,
        'is_enabled' => true,
        'created_by' => 'config',
    ]);

    NotificationSetting::query()->create([
        'notification_type_id' => $type->id,
        'channel' => 'whatsapp',
        'is_enabled' => $settingEnabled,
    ]);

    return $type;
}

it('does not re-query the database on a second type lookup', function (): void {
    cachingMakeType();

    /** @var NotificationCenterCache $cache */
    $cache = app(NotificationCenterCache::class);

    // Prime the cache outside the query log.
    $cache->type('order.confirmed');

    DB::enableQueryLog();
    DB::flushQueryLog();

    $cache->type('order.confirmed');
    $cache->type('order.confirmed');

    expect(DB::getQueryLog())->toHaveCount(0);

    DB::disableQueryLog();
});

it('runs exactly one query on a cold type lookup', function (): void {
    cachingMakeType();

    /** @var NotificationCenterCache $cache */
    $cache = app(NotificationCenterCache::class);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $cache->type('order.confirmed');

    expect(DB::getQueryLog())->toHaveCount(1);

    DB::disableQueryLog();
});

it('caches the channel setting lookup', function (): void {
    $type = cachingMakeType();

    /** @var NotificationCenterCache $cache */
    $cache = app(NotificationCenterCache::class);

    $cache->settingEnabled($type->id, 'whatsapp');

    DB::enableQueryLog();
    DB::flushQueryLog();

    expect($cache->settingEnabled($type->id, 'whatsapp'))->toBeTrue();
    expect(DB::getQueryLog())->toHaveCount(0);

    DB::disableQueryLog();
});

it('refreshes the cached type after a targeted forget', function (): void {
    cachingMakeType();

    /** @var NotificationCenterCache $cache */
    $cache = app(NotificationCenterCache::class);

    expect($cache->type('order.confirmed')?->is_enabled)->toBeTrue();

    NotificationType::query()->where('key', 'order.confirmed')->update(['is_enabled' => false]);

    // Without forgetting, the stale cached value persists.
    expect($cache->type('order.confirmed')?->is_enabled)->toBeTrue();

    $cache->forgetType('order.confirmed');

    expect($cache->type('order.confirmed')?->is_enabled)->toBeFalse();
});

it('refreshes the cached channel setting after a targeted forget', function (): void {
    $type = cachingMakeType(settingEnabled: true);

    /** @var NotificationCenterCache $cache */
    $cache = app(NotificationCenterCache::class);

    expect($cache->settingEnabled($type->id, 'whatsapp'))->toBeTrue();

    NotificationSetting::query()
        ->where('notification_type_id', $type->id)
        ->where('channel', 'whatsapp')
        ->update(['is_enabled' => false]);

    $cache->forgetSettings($type->id);

    expect($cache->settingEnabled($type->id, 'whatsapp'))->toBeFalse();
});

it('bypasses the cache entirely when caching is disabled', function (): void {
    config()->set('notification-center.cache.enabled', false);

    cachingMakeType();

    /** @var NotificationCenterCache $cache */
    $cache = app(NotificationCenterCache::class);

    $cache->type('order.confirmed');

    DB::enableQueryLog();
    DB::flushQueryLog();

    $cache->type('order.confirmed');
    $cache->type('order.confirmed');

    // Every read hits the database when caching is disabled.
    expect(DB::getQueryLog())->toHaveCount(2);

    DB::disableQueryLog();
});

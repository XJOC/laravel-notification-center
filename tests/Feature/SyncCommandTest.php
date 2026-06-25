<?php

declare(strict_types=1);

use XJOC\NotificationCenter\Enums\CreatedBy;
use XJOC\NotificationCenter\Enums\NotificationCategory;
use XJOC\NotificationCenter\Models\NotificationSetting;
use XJOC\NotificationCenter\Models\NotificationTemplate;
use XJOC\NotificationCenter\Models\NotificationType;

beforeEach(function (): void {
    config()->set('notification-center.types', [
        'order.confirmed' => [
            'name' => 'Order Confirmed',
            'category' => 'transactional',
            'channels' => ['mail', 'whatsapp'],
            'locked' => false,
            'variables' => ['customer_name', 'order_id', 'total'],
        ],
        'otp.sent' => [
            'name' => 'OTP Sent',
            'category' => 'essential',
            'channels' => ['whatsapp'],
            'locked' => true,
            'variables' => ['otp_code', 'expires_in'],
        ],
    ]);
});

it('creates the coded types from config', function (): void {
    $this->artisanCommand('notification-center:sync')->assertSuccessful();

    expect(NotificationType::query()->count())->toBe(2);

    /** @var NotificationType $order */
    $order = NotificationType::query()->where('key', 'order.confirmed')->firstOrFail();
    expect($order->category)->toBe(NotificationCategory::Transactional);
    expect($order->supported_channels)->toBe(['mail', 'whatsapp']);
    expect($order->is_enabled)->toBeTrue();
    expect($order->is_locked)->toBeFalse();
    expect($order->created_by)->toBe(CreatedBy::Config);
});

it('marks essential coded types as locked', function (): void {
    $this->artisanCommand('notification-center:sync')->assertSuccessful();

    /** @var NotificationType $otp */
    $otp = NotificationType::query()->where('key', 'otp.sent')->firstOrFail();
    expect($otp->category)->toBe(NotificationCategory::Essential);
    expect($otp->is_locked)->toBeTrue();
});

it('creates default enabled settings for every channel', function (): void {
    $this->artisanCommand('notification-center:sync')->assertSuccessful();

    /** @var NotificationType $order */
    $order = NotificationType::query()->where('key', 'order.confirmed')->firstOrFail();

    expect(NotificationSetting::query()->where('notification_type_id', $order->id)->count())->toBe(2);
    expect(
        NotificationSetting::query()
            ->where('notification_type_id', $order->id)
            ->where('channel', 'mail')
            ->value('is_enabled')
    )->toBe(true);
});

it('is idempotent and does not duplicate types or settings', function (): void {
    $this->artisanCommand('notification-center:sync')->assertSuccessful();
    $this->artisanCommand('notification-center:sync')->assertSuccessful();

    expect(NotificationType::query()->count())->toBe(2);

    /** @var NotificationType $order */
    $order = NotificationType::query()->where('key', 'order.confirmed')->firstOrFail();
    expect(NotificationSetting::query()->where('notification_type_id', $order->id)->count())->toBe(2);
});

it('preserves is_enabled when re-syncing an existing config type', function (): void {
    $this->artisanCommand('notification-center:sync')->assertSuccessful();

    NotificationType::query()->where('key', 'order.confirmed')->update(['is_enabled' => false]);

    $this->artisanCommand('notification-center:sync')->assertSuccessful();

    /** @var NotificationType $order */
    $order = NotificationType::query()->where('key', 'order.confirmed')->firstOrFail();
    expect($order->is_enabled)->toBeFalse();
});

it('updates structural fields on an existing config type', function (): void {
    $this->artisanCommand('notification-center:sync')->assertSuccessful();

    NotificationType::query()->where('key', 'order.confirmed')->update([
        'name' => 'Stale Name',
        'supported_channels' => ['mail'],
    ]);

    $this->artisanCommand('notification-center:sync')->assertSuccessful();

    /** @var NotificationType $order */
    $order = NotificationType::query()->where('key', 'order.confirmed')->firstOrFail();
    expect($order->name)->toBe('Order Confirmed');
    expect($order->supported_channels)->toBe(['mail', 'whatsapp']);
});

it('never touches an admin-created row with the same key', function (): void {
    /** @var NotificationType $admin */
    $admin = NotificationType::query()->create([
        'key' => 'order.confirmed',
        'name' => 'Admin Owned',
        'category' => 'marketing',
        'supported_channels' => ['mail'],
        'variables' => [],
        'is_locked' => false,
        'is_enabled' => false,
        'created_by' => 'admin',
    ]);

    $this->artisanCommand('notification-center:sync')->assertSuccessful();

    $admin->refresh();
    expect($admin->name)->toBe('Admin Owned');
    expect($admin->category)->toBe(NotificationCategory::Marketing);
    expect($admin->supported_channels)->toBe(['mail']);
    expect($admin->is_enabled)->toBeFalse();
    expect($admin->created_by)->toBe(CreatedBy::Admin);

    // Sync must not create config-driven settings rows on an admin-owned type.
    expect(NotificationSetting::query()->where('notification_type_id', $admin->id)->count())->toBe(0);
});

it('never creates or modifies templates', function (): void {
    $this->artisanCommand('notification-center:sync')->assertSuccessful();

    expect(NotificationTemplate::query()->count())->toBe(0);
});

<?php

declare(strict_types=1);

use XJOC\NotificationCenter\Enums\CreatedBy;
use XJOC\NotificationCenter\Enums\NotificationCategory;
use XJOC\NotificationCenter\Models\NotificationSetting;
use XJOC\NotificationCenter\Models\NotificationType;

it('lists notification types with their settings', function (): void {
    $type = NotificationType::query()->create([
        'key' => 'order.confirmed',
        'name' => 'Order Confirmed',
        'category' => NotificationCategory::Transactional,
        'supported_channels' => ['mail', 'whatsapp'],
        'variables' => ['order_id'],
        'is_locked' => false,
        'is_enabled' => true,
        'created_by' => CreatedBy::Config,
    ]);

    $type->settings()->create(['channel' => 'mail', 'is_enabled' => true]);
    $type->settings()->create(['channel' => 'whatsapp', 'is_enabled' => true]);

    $response = $this->getJson('notification-center/admin/types');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.key', 'order.confirmed')
        ->assertJsonPath('data.0.category', 'transactional')
        ->assertJsonPath('data.0.created_by', 'config')
        ->assertJsonCount(2, 'data.0.settings');
});

it('stores a new transactional type and creates default settings', function (): void {
    $response = $this->postJson('notification-center/admin/types', [
        'key' => 'invoice.paid',
        'name' => 'Invoice Paid',
        'category' => 'transactional',
        'channels' => ['mail', 'database'],
        'variables' => ['invoice_id'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.key', 'invoice.paid')
        ->assertJsonPath('data.category', 'transactional')
        ->assertJsonPath('data.is_locked', false)
        ->assertJsonPath('data.is_enabled', true)
        ->assertJsonPath('data.created_by', 'admin')
        ->assertJsonCount(2, 'data.settings');

    $type = NotificationType::query()->where('key', 'invoice.paid')->firstOrFail();

    expect($type->created_by)->toBe(CreatedBy::Admin)
        ->and($type->is_locked)->toBeFalse();

    expect(NotificationSetting::query()->where('notification_type_id', $type->id)->count())->toBe(2);
});

it('forces lock and enabled when storing an essential type', function (): void {
    $response = $this->postJson('notification-center/admin/types', [
        'key' => 'security.otp',
        'name' => 'Security OTP',
        'category' => 'essential',
        'channels' => ['whatsapp'],
        'locked' => false,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.is_locked', true)
        ->assertJsonPath('data.is_enabled', true);

    $type = NotificationType::query()->where('key', 'security.otp')->firstOrFail();

    expect($type->is_locked)->toBeTrue()
        ->and($type->is_enabled)->toBeTrue();
});

it('validates store input', function (): void {
    $this->postJson('notification-center/admin/types', [
        'name' => 'No Key',
        'category' => 'transactional',
        'channels' => ['mail'],
    ])->assertStatus(422)->assertJsonValidationErrors(['key']);

    $this->postJson('notification-center/admin/types', [
        'key' => 'bad.category',
        'name' => 'Bad Category',
        'category' => 'not-a-category',
        'channels' => ['mail'],
    ])->assertStatus(422)->assertJsonValidationErrors(['category']);

    $this->postJson('notification-center/admin/types', [
        'key' => 'bad.channel',
        'name' => 'Bad Channel',
        'category' => 'transactional',
        'channels' => ['carrier-pigeon'],
    ])->assertStatus(422)->assertJsonValidationErrors(['channels.0']);
});

it('updates a type toggling name and enabled state', function (): void {
    $type = NotificationType::query()->create([
        'key' => 'order.confirmed',
        'name' => 'Order Confirmed',
        'category' => NotificationCategory::Transactional,
        'supported_channels' => ['mail'],
        'variables' => [],
        'is_locked' => false,
        'is_enabled' => true,
        'created_by' => CreatedBy::Admin,
    ]);
    $type->settings()->create(['channel' => 'mail', 'is_enabled' => true]);

    $response = $this->patchJson("notification-center/admin/types/{$type->id}", [
        'name' => 'Order Confirmation',
        'is_enabled' => false,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Order Confirmation')
        ->assertJsonPath('data.is_enabled', false);

    $fresh = $type->fresh();
    expect($fresh)->not->toBeNull();
    expect($fresh?->is_enabled)->toBeFalse();
});

it('creates settings for newly added channels on update', function (): void {
    $type = NotificationType::query()->create([
        'key' => 'order.confirmed',
        'name' => 'Order Confirmed',
        'category' => NotificationCategory::Transactional,
        'supported_channels' => ['mail'],
        'variables' => [],
        'is_locked' => false,
        'is_enabled' => true,
        'created_by' => CreatedBy::Admin,
    ]);
    $type->settings()->create(['channel' => 'mail', 'is_enabled' => true]);

    $response = $this->patchJson("notification-center/admin/types/{$type->id}", [
        'supported_channels' => ['mail', 'whatsapp'],
    ]);

    $response->assertOk()
        ->assertJsonCount(2, 'data.settings');

    expect(
        NotificationSetting::query()
            ->where('notification_type_id', $type->id)
            ->where('channel', 'whatsapp')
            ->where('is_enabled', true)
            ->exists()
    )->toBeTrue();
});

it('rejects disabling an essential type with 422', function (): void {
    $type = NotificationType::query()->create([
        'key' => 'otp.sent',
        'name' => 'OTP Sent',
        'category' => NotificationCategory::Essential,
        'supported_channels' => ['whatsapp'],
        'variables' => [],
        'is_locked' => true,
        'is_enabled' => true,
        'created_by' => CreatedBy::Config,
    ]);

    $this->patchJson("notification-center/admin/types/{$type->id}", [
        'is_enabled' => false,
    ])->assertStatus(422)->assertJsonValidationErrors(['is_enabled']);

    $fresh = $type->fresh();
    expect($fresh)->not->toBeNull();
    expect($fresh?->is_enabled)->toBeTrue();
});

it('rejects disabling a locked non-essential type with 422', function (): void {
    $type = NotificationType::query()->create([
        'key' => 'account.security',
        'name' => 'Account Security',
        'category' => NotificationCategory::Alerts,
        'supported_channels' => ['mail'],
        'variables' => [],
        'is_locked' => true,
        'is_enabled' => true,
        'created_by' => CreatedBy::Admin,
    ]);

    $this->patchJson("notification-center/admin/types/{$type->id}", [
        'is_enabled' => false,
    ])->assertStatus(422)->assertJsonValidationErrors(['is_enabled']);

    $fresh = $type->fresh();
    expect($fresh)->not->toBeNull();
    expect($fresh?->is_enabled)->toBeTrue();
});

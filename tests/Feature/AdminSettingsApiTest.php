<?php

declare(strict_types=1);

use Xjoc\NotificationCenter\Enums\CreatedBy;
use Xjoc\NotificationCenter\Enums\NotificationCategory;
use Xjoc\NotificationCenter\Models\NotificationType;

it('returns a settings overview of every type with its channel settings', function (): void {
    $order = NotificationType::query()->create([
        'key' => 'order.confirmed',
        'name' => 'Order Confirmed',
        'category' => NotificationCategory::Transactional,
        'supported_channels' => ['mail', 'whatsapp'],
        'variables' => [],
        'is_locked' => false,
        'is_enabled' => true,
        'created_by' => CreatedBy::Config,
    ]);
    $order->settings()->create(['channel' => 'mail', 'is_enabled' => true]);
    $order->settings()->create(['channel' => 'whatsapp', 'is_enabled' => false]);

    $otp = NotificationType::query()->create([
        'key' => 'otp.sent',
        'name' => 'OTP Sent',
        'category' => NotificationCategory::Essential,
        'supported_channels' => ['whatsapp'],
        'variables' => [],
        'is_locked' => true,
        'is_enabled' => true,
        'created_by' => CreatedBy::Config,
    ]);
    $otp->settings()->create(['channel' => 'whatsapp', 'is_enabled' => true]);

    $response = $this->getJson('notification-center/admin/settings');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'key',
                    'name',
                    'category',
                    'supported_channels',
                    'is_locked',
                    'is_enabled',
                    'created_by',
                    'settings' => [
                        ['id', 'notification_type_id', 'channel', 'is_enabled'],
                    ],
                ],
            ],
        ]);

    $response->assertJsonPath('data.0.key', 'order.confirmed')
        ->assertJsonCount(2, 'data.0.settings')
        ->assertJsonCount(1, 'data.1.settings');
});

it('returns an empty data set when there are no types', function (): void {
    $this->getJson('notification-center/admin/settings')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

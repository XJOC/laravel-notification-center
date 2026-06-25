<?php

declare(strict_types=1);

use Xjoc\NotificationCenter\Models\NotificationSetting;
use Xjoc\NotificationCenter\Models\NotificationTemplate;
use Xjoc\NotificationCenter\Models\NotificationType;
use Xjoc\NotificationCenter\Models\NotificationUserPreference;
use Xjoc\NotificationCenter\Tests\Fixtures\NotificationSpy;
use Xjoc\NotificationCenter\Tests\Fixtures\OrderConfirmedNotification;
use Xjoc\NotificationCenter\Tests\Fixtures\User;

/**
 * @param  array<int, string>  $channels
 */
function precedenceMakeType(
    string $category = 'transactional',
    array $channels = ['whatsapp'],
    bool $isEnabled = true,
    bool $isLocked = false,
    bool $settingEnabled = true,
): NotificationType {
    /** @var NotificationType $type */
    $type = NotificationType::query()->create([
        'key' => 'order.confirmed',
        'name' => 'Order Confirmed',
        'category' => $category,
        'supported_channels' => $channels,
        'variables' => ['customer_name', 'order_id', 'total'],
        'is_locked' => $isLocked || $category === 'essential',
        'is_enabled' => $isEnabled,
        'created_by' => 'config',
    ]);

    foreach ($channels as $channel) {
        NotificationSetting::query()->create([
            'notification_type_id' => $type->id,
            'channel' => $channel,
            'is_enabled' => $settingEnabled,
        ]);

        NotificationTemplate::query()->create([
            'notification_type_id' => $type->id,
            'channel' => $channel,
            'subject' => 'Hi {{ customer_name }}',
            'body' => 'Order {{ order_id }}',
        ]);
    }

    return $type;
}

function precedenceUser(): User
{
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    return $user;
}

it('blocks delivery when only the type master switch is disabled', function (): void {
    $user = precedenceUser();
    precedenceMakeType(isEnabled: false, settingEnabled: true);

    $user->notify(new OrderConfirmedNotification);

    expect(NotificationSpy::count('whatsapp'))->toBe(0);
});

it('blocks delivery when only the channel setting is disabled', function (): void {
    $user = precedenceUser();
    precedenceMakeType(isEnabled: true, settingEnabled: false);

    $user->notify(new OrderConfirmedNotification);

    expect(NotificationSpy::count('whatsapp'))->toBe(0);
});

it('delivers when both the type switch and the channel setting are enabled', function (): void {
    $user = precedenceUser();
    precedenceMakeType(isEnabled: true, settingEnabled: true);

    $user->notify(new OrderConfirmedNotification);

    expect(NotificationSpy::sentVia('whatsapp'))->toBeTrue();
});

it('still gates a locked non-essential type through the channel setting', function (): void {
    $user = precedenceUser();
    // Locked prevents an admin from disabling the type, but a disabled channel
    // setting must still block delivery.
    precedenceMakeType(isEnabled: true, isLocked: true, settingEnabled: false);

    $user->notify(new OrderConfirmedNotification);

    expect(NotificationSpy::count('whatsapp'))->toBe(0);
});

it('delivers a locked non-essential type when its channel setting is enabled', function (): void {
    $user = precedenceUser();
    precedenceMakeType(isEnabled: true, isLocked: true, settingEnabled: true);

    $user->notify(new OrderConfirmedNotification);

    expect(NotificationSpy::sentVia('whatsapp'))->toBeTrue();
});

it('always delivers an essential type even when disabled, setting-off and opted-out', function (): void {
    $user = precedenceUser();
    $type = precedenceMakeType(category: 'essential', isEnabled: false, settingEnabled: false);

    NotificationUserPreference::query()->create([
        'notifiable_type' => $user->getMorphClass(),
        'notifiable_id' => $user->getKey(),
        'notification_type_id' => $type->id,
        'channel' => 'whatsapp',
        'opted_out' => true,
    ]);

    $user->notify(new OrderConfirmedNotification);

    expect(NotificationSpy::sentVia('whatsapp'))->toBeTrue();
});

it('ignores a user opt-out for an essential type', function (): void {
    $user = precedenceUser();
    $type = precedenceMakeType(category: 'essential', isEnabled: true, settingEnabled: true);

    NotificationUserPreference::query()->create([
        'notifiable_type' => $user->getMorphClass(),
        'notifiable_id' => $user->getKey(),
        'notification_type_id' => $type->id,
        'channel' => 'whatsapp',
        'opted_out' => true,
    ]);

    $user->notify(new OrderConfirmedNotification);

    expect(NotificationSpy::sentVia('whatsapp'))->toBeTrue();
});

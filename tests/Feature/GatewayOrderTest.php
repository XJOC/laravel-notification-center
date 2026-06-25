<?php

declare(strict_types=1);

use Illuminate\Notifications\Notification;
use XJOC\NotificationCenter\Models\NotificationSetting;
use XJOC\NotificationCenter\Models\NotificationTemplate;
use XJOC\NotificationCenter\Models\NotificationType;
use XJOC\NotificationCenter\Models\NotificationUserPreference;
use XJOC\NotificationCenter\Tests\Fixtures\NotificationSpy;
use XJOC\NotificationCenter\Tests\Fixtures\OrderConfirmedNotification;
use XJOC\NotificationCenter\Tests\Fixtures\User;

/**
 * Create a NotificationType row plus a matching setting + template for the
 * whatsapp channel so the gateway has data to gate against.
 *
 * @param  array<int, string>  $channels
 */
function gatewayMakeType(
    string $key,
    string $category = 'transactional',
    array $channels = ['whatsapp'],
    bool $isEnabled = true,
    bool $settingEnabled = true,
): NotificationType {
    /** @var NotificationType $type */
    $type = NotificationType::query()->create([
        'key' => $key,
        'name' => 'Test Type',
        'category' => $category,
        'supported_channels' => $channels,
        'variables' => ['customer_name', 'order_id', 'total'],
        'is_locked' => $category === 'essential',
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
            'subject' => 'Hello {{ customer_name }}',
            'body' => 'Order {{ order_id }} for {{ total }}',
        ]);
    }

    return $type;
}

it('passes through notifications that are not NotifiableNotification instances', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    $plain = new class extends Notification
    {
        /** @return array<int, string> */
        public function via(object $notifiable): array
        {
            return ['whatsapp'];
        }

        public function toWhatsapp(object $notifiable): string
        {
            return 'plain body';
        }
    };

    $user->notify($plain);

    expect(NotificationSpy::sentVia('whatsapp'))->toBeTrue();
    expect(NotificationSpy::count('whatsapp'))->toBe(1);
});

it('passes through notifications whose type is unknown to the database', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    // No NotificationType row exists for order.confirmed, so via() resolves to
    // an empty channel list and nothing is captured, but no exception is thrown.
    $user->notify(new OrderConfirmedNotification);

    expect(NotificationSpy::count('whatsapp'))->toBe(0);
});

it('bypasses the gateway for essential types even when disabled and opted-out', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    gatewayMakeType('order.confirmed', 'essential', ['whatsapp'], isEnabled: false, settingEnabled: false);

    $user->notify(new OrderConfirmedNotification);

    expect(NotificationSpy::sentVia('whatsapp'))->toBeTrue();
    expect(NotificationSpy::forChannel('whatsapp')[0]['payload'])->toBe('Order 42 for $10');
});

it('blocks delivery when the type master switch is disabled', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    gatewayMakeType('order.confirmed', 'transactional', ['whatsapp'], isEnabled: false);

    $user->notify(new OrderConfirmedNotification);

    expect(NotificationSpy::count('whatsapp'))->toBe(0);
});

it('blocks delivery when the admin channel setting is disabled', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    gatewayMakeType('order.confirmed', 'transactional', ['whatsapp'], settingEnabled: false);

    $user->notify(new OrderConfirmedNotification);

    expect(NotificationSpy::count('whatsapp'))->toBe(0);
});

it('blocks delivery when the user has opted out of the channel', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    $type = gatewayMakeType('order.confirmed', 'transactional', ['whatsapp']);

    NotificationUserPreference::query()->create([
        'notifiable_type' => $user->getMorphClass(),
        'notifiable_id' => $user->getKey(),
        'notification_type_id' => $type->id,
        'channel' => 'whatsapp',
        'opted_out' => true,
    ]);

    $user->notify(new OrderConfirmedNotification);

    expect(NotificationSpy::count('whatsapp'))->toBe(0);
});

it('allows delivery and injects the rendered template when every gate passes', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    gatewayMakeType('order.confirmed', 'transactional', ['whatsapp']);

    $user->notify(new OrderConfirmedNotification);

    expect(NotificationSpy::sentVia('whatsapp'))->toBeTrue();
    expect(NotificationSpy::forChannel('whatsapp')[0]['payload'])->toBe('Order 42 for $10');
});

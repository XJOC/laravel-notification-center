<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Xjoc\NotificationCenter\Facades\NotificationCenter;
use Xjoc\NotificationCenter\Models\NotificationSetting;
use Xjoc\NotificationCenter\Models\NotificationTemplate;
use Xjoc\NotificationCenter\Models\NotificationType;
use Xjoc\NotificationCenter\Tests\Fixtures\NotificationSpy;
use Xjoc\NotificationCenter\Tests\Fixtures\User;

/**
 * @param  array<int, string>  $channels
 */
function dispatchMakeType(array $channels = ['mail', 'whatsapp']): NotificationType
{
    /** @var NotificationType $type */
    $type = NotificationType::query()->create([
        'key' => 'order.confirmed',
        'name' => 'Order Confirmed',
        'category' => 'transactional',
        'supported_channels' => $channels,
        'variables' => ['customer_name', 'order_id'],
        'is_locked' => false,
        'is_enabled' => true,
        'created_by' => 'config',
    ]);

    foreach ($channels as $channel) {
        NotificationSetting::query()->create([
            'notification_type_id' => $type->id,
            'channel' => $channel,
            'is_enabled' => true,
        ]);

        NotificationTemplate::query()->create([
            'notification_type_id' => $type->id,
            'channel' => $channel,
            'subject' => 'Hi {{ customer_name }}',
            'body' => 'Order {{ order_id }} via '.$channel,
        ]);
    }

    return $type;
}

function dispatchUser(string $email): User
{
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => $email, 'password' => 'secret']);

    return $user;
}

it('dispatches to a single notifiable across all supported channels', function (): void {
    $user = dispatchUser('a@example.test');
    dispatchMakeType(['mail', 'whatsapp']);

    NotificationCenter::send('order.confirmed', $user, ['customer_name' => 'Sam', 'order_id' => '7']);

    expect(NotificationSpy::sentVia('mail'))->toBeTrue();
    expect(NotificationSpy::sentVia('whatsapp'))->toBeTrue();
    expect(NotificationSpy::count('whatsapp'))->toBe(1);
});

it('dispatches to a collection of notifiables', function (): void {
    $userA = dispatchUser('a@example.test');
    $userB = dispatchUser('b@example.test');
    dispatchMakeType(['whatsapp']);

    /** @var Collection<int, User> $recipients */
    $recipients = new Collection([$userA, $userB]);

    NotificationCenter::send('order.confirmed', $recipients, ['customer_name' => 'Sam', 'order_id' => '7']);

    expect(NotificationSpy::count('whatsapp'))->toBe(2);
});

it('restricts dispatch to an explicit channel subset', function (): void {
    $user = dispatchUser('a@example.test');
    dispatchMakeType(['mail', 'whatsapp']);

    NotificationCenter::send('order.confirmed', $user, ['customer_name' => 'Sam', 'order_id' => '7'], ['whatsapp']);

    expect(NotificationSpy::sentVia('whatsapp'))->toBeTrue();
    expect(NotificationSpy::sentVia('mail'))->toBeFalse();
});

it('passes through the gateway and injects the rendered template on dispatch', function (): void {
    $user = dispatchUser('a@example.test');
    dispatchMakeType(['whatsapp']);

    NotificationCenter::send('order.confirmed', $user, ['customer_name' => 'Sam', 'order_id' => '99'], ['whatsapp']);

    expect(NotificationSpy::forChannel('whatsapp')[0]['payload'])->toBe('Order 99 via whatsapp');
});

it('does not deliver a manually dispatched notification when the type is disabled', function (): void {
    $user = dispatchUser('a@example.test');
    $type = dispatchMakeType(['whatsapp']);
    $type->update(['is_enabled' => false]);

    NotificationCenter::send('order.confirmed', $user, ['customer_name' => 'Sam', 'order_id' => '7'], ['whatsapp']);

    expect(NotificationSpy::count('whatsapp'))->toBe(0);
});

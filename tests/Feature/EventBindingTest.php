<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use XJOC\NotificationCenter\Contracts\ProvidesNotificationContext;
use XJOC\NotificationCenter\Listeners\EventBindingListener;
use XJOC\NotificationCenter\Models\NotificationEventBinding;
use XJOC\NotificationCenter\Models\NotificationSetting;
use XJOC\NotificationCenter\Models\NotificationTemplate;
use XJOC\NotificationCenter\Models\NotificationType;
use XJOC\NotificationCenter\Support\NotificationCenterCache;
use XJOC\NotificationCenter\Tests\Fixtures\NotificationSpy;
use XJOC\NotificationCenter\Tests\Fixtures\User;

/**
 * Event that carries notification context (recipients + variables).
 */
final class OrderShippedEvent implements ProvidesNotificationContext
{
    /**
     * @param  iterable<int, object>  $recipients
     */
    public function __construct(private iterable $recipients) {}

    /**
     * @return iterable<int, object>
     */
    public function notificationRecipients(): iterable
    {
        return $this->recipients;
    }

    /**
     * @return array<string, mixed>
     */
    public function notificationVariables(): array
    {
        return ['customer_name' => 'Sam', 'order_id' => '88'];
    }
}

/**
 * Event that does NOT implement ProvidesNotificationContext.
 */
final class OrderPlainEvent
{
    public function __construct(public string $note = 'nope') {}
}

/**
 * @param  array<int, string>  $channels
 */
function eventMakeType(array $channels = ['whatsapp']): NotificationType
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
            'subject' => null,
            'body' => 'Order {{ order_id }}',
        ]);
    }

    return $type;
}

function eventUser(): User
{
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    return $user;
}

/**
 * The provider registers event-binding listeners at boot time, before any
 * binding rows exist in the test DB. After creating a binding and flushing the
 * cache we manually re-register the listener to simulate a fresh boot.
 */
function eventRegisterListener(): void
{
    app(NotificationCenterCache::class)->forgetEventBindings();
    Event::listen(OrderShippedEvent::class, [EventBindingListener::class, 'handle']);
}

it('dispatches notifications for an event bound to an enabled type', function (): void {
    $user = eventUser();
    $type = eventMakeType(['whatsapp']);

    NotificationEventBinding::query()->create([
        'notification_type_id' => $type->id,
        'event_class' => OrderShippedEvent::class,
        'is_active' => true,
    ]);

    eventRegisterListener();

    Event::dispatch(new OrderShippedEvent([$user]));

    expect(NotificationSpy::sentVia('whatsapp'))->toBeTrue();
    expect(NotificationSpy::forChannel('whatsapp')[0]['payload'])->toBe('Order 88');
});

it('does not dispatch for an event that has no active binding', function (): void {
    $user = eventUser();
    eventMakeType(['whatsapp']);

    // No binding row created.
    eventRegisterListener();

    Event::dispatch(new OrderShippedEvent([$user]));

    expect(NotificationSpy::count('whatsapp'))->toBe(0);
});

it('does not dispatch for an inactive binding', function (): void {
    $user = eventUser();
    $type = eventMakeType(['whatsapp']);

    NotificationEventBinding::query()->create([
        'notification_type_id' => $type->id,
        'event_class' => OrderShippedEvent::class,
        'is_active' => false,
    ]);

    eventRegisterListener();

    Event::dispatch(new OrderShippedEvent([$user]));

    expect(NotificationSpy::count('whatsapp'))->toBe(0);
});

it('does not dispatch for an active binding whose non-essential type is disabled', function (): void {
    $user = eventUser();

    /** @var NotificationType $type */
    $type = NotificationType::query()->create([
        'key' => 'order.confirmed',
        'name' => 'Order Confirmed',
        'category' => 'transactional',
        'supported_channels' => ['whatsapp'],
        'variables' => ['customer_name', 'order_id'],
        'is_locked' => false,
        'is_enabled' => false,
        'created_by' => 'config',
    ]);

    NotificationSetting::query()->create([
        'notification_type_id' => $type->id,
        'channel' => 'whatsapp',
        'is_enabled' => true,
    ]);

    NotificationTemplate::query()->create([
        'notification_type_id' => $type->id,
        'channel' => 'whatsapp',
        'subject' => null,
        'body' => 'Order {{ order_id }}',
    ]);

    NotificationEventBinding::query()->create([
        'notification_type_id' => $type->id,
        'event_class' => OrderShippedEvent::class,
        'is_active' => true,
    ]);

    eventRegisterListener();

    Event::dispatch(new OrderShippedEvent([$user]));

    expect(NotificationSpy::count('whatsapp'))->toBe(0);
});

it('still dispatches for an active binding whose essential type is disabled', function (): void {
    $user = eventUser();

    /** @var NotificationType $type */
    $type = NotificationType::query()->create([
        'key' => 'order.confirmed',
        'name' => 'Order Confirmed',
        'category' => 'essential',
        'supported_channels' => ['whatsapp'],
        'variables' => ['customer_name', 'order_id'],
        'is_locked' => true,
        'is_enabled' => false,
        'created_by' => 'config',
    ]);

    NotificationSetting::query()->create([
        'notification_type_id' => $type->id,
        'channel' => 'whatsapp',
        'is_enabled' => true,
    ]);

    NotificationTemplate::query()->create([
        'notification_type_id' => $type->id,
        'channel' => 'whatsapp',
        'subject' => null,
        'body' => 'Order {{ order_id }}',
    ]);

    NotificationEventBinding::query()->create([
        'notification_type_id' => $type->id,
        'event_class' => OrderShippedEvent::class,
        'is_active' => true,
    ]);

    eventRegisterListener();

    Event::dispatch(new OrderShippedEvent([$user]));

    expect(NotificationSpy::sentVia('whatsapp'))->toBeTrue();
});

it('ignores events that do not provide notification context', function (): void {
    $type = eventMakeType(['whatsapp']);

    NotificationEventBinding::query()->create([
        'notification_type_id' => $type->id,
        'event_class' => OrderPlainEvent::class,
        'is_active' => true,
    ]);

    app(NotificationCenterCache::class)->forgetEventBindings();
    Event::listen(OrderPlainEvent::class, [EventBindingListener::class, 'handle']);

    Event::dispatch(new OrderPlainEvent);

    expect(NotificationSpy::count('whatsapp'))->toBe(0);
});

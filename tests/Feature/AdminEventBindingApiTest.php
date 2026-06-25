<?php

declare(strict_types=1);

use XJOC\NotificationCenter\Enums\CreatedBy;
use XJOC\NotificationCenter\Enums\NotificationCategory;
use XJOC\NotificationCenter\Models\NotificationEventBinding;
use XJOC\NotificationCenter\Models\NotificationType;
use XJOC\NotificationCenter\Tests\Fixtures\OrderShippedEvent;

function makeBindingType(): NotificationType
{
    return NotificationType::query()->create([
        'key' => 'order.confirmed',
        'name' => 'Order Confirmed',
        'category' => NotificationCategory::Transactional,
        'supported_channels' => ['mail'],
        'variables' => [],
        'is_locked' => false,
        'is_enabled' => true,
        'created_by' => CreatedBy::Admin,
    ]);
}

it('lists event bindings for a type', function (): void {
    $type = makeBindingType();
    $type->eventBindings()->create([
        'event_class' => 'App\\Events\\OrderPlaced',
        'is_active' => true,
    ]);

    $response = $this->getJson("notification-center/admin/types/{$type->id}/event-bindings");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.event_class', 'App\\Events\\OrderPlaced')
        ->assertJsonPath('data.0.is_active', true)
        ->assertJsonPath('data.0.notification_type_id', $type->id);
});

it('stores an event binding with 201', function (): void {
    $type = makeBindingType();

    $response = $this->postJson("notification-center/admin/types/{$type->id}/event-bindings", [
        'event_class' => OrderShippedEvent::class,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.event_class', OrderShippedEvent::class)
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.notification_type_id', $type->id);

    expect(
        NotificationEventBinding::query()
            ->where('notification_type_id', $type->id)
            ->where('event_class', OrderShippedEvent::class)
            ->exists()
    )->toBeTrue();
});

it('validates the event class is required', function (): void {
    $type = makeBindingType();

    $this->postJson("notification-center/admin/types/{$type->id}/event-bindings", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['event_class']);
});

it('destroys an event binding with 204', function (): void {
    $type = makeBindingType();
    $binding = $type->eventBindings()->create([
        'event_class' => 'App\\Events\\OrderPlaced',
        'is_active' => true,
    ]);

    $this->deleteJson("notification-center/admin/event-bindings/{$binding->id}")
        ->assertNoContent();

    expect(NotificationEventBinding::query()->whereKey($binding->id)->exists())->toBeFalse();
});

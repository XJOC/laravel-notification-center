<?php

declare(strict_types=1);

use Xjoc\NotificationCenter\Enums\CreatedBy;
use Xjoc\NotificationCenter\Enums\NotificationCategory;
use Xjoc\NotificationCenter\Models\NotificationType;
use Xjoc\NotificationCenter\Tests\Fixtures\NotificationSpy;
use Xjoc\NotificationCenter\Tests\Fixtures\User;

/**
 * @param  array<int, string>  $channels
 */
function makeDispatchType(array $channels = ['mail', 'whatsapp']): NotificationType
{
    $type = NotificationType::query()->create([
        'key' => 'order.confirmed',
        'name' => 'Order Confirmed',
        'category' => NotificationCategory::Transactional,
        'supported_channels' => $channels,
        'variables' => ['order_id'],
        'is_locked' => false,
        'is_enabled' => true,
        'created_by' => CreatedBy::Admin,
    ]);

    foreach ($channels as $channel) {
        $type->settings()->create(['channel' => $channel, 'is_enabled' => true]);
        $type->templates()->create([
            'channel' => $channel,
            'subject' => $channel === 'mail' ? 'Order {{ order_id }}' : null,
            'body' => 'Order {{ order_id }} confirmed.',
        ]);
    }

    return $type;
}

it('dispatches a notification and resolves recipients', function (): void {
    $type = makeDispatchType();

    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test']);

    $response = $this->postJson("notification-center/admin/types/{$type->id}/dispatch", [
        'recipients' => [
            'model' => User::class,
            'ids' => [$user->id],
        ],
        'variables' => ['order_id' => '99'],
    ]);

    $response->assertStatus(202)
        ->assertJsonPath('message', 'Notification dispatched.');

    expect(NotificationSpy::sentVia('mail'))->toBeTrue()
        ->and(NotificationSpy::sentVia('whatsapp'))->toBeTrue();
});

it('dispatches to a channel subset when channels are provided', function (): void {
    $type = makeDispatchType();

    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test']);

    $this->postJson("notification-center/admin/types/{$type->id}/dispatch", [
        'recipients' => [
            'model' => User::class,
            'ids' => [$user->id],
        ],
        'variables' => ['order_id' => '99'],
        'channels' => ['whatsapp'],
    ])->assertStatus(202);

    expect(NotificationSpy::sentVia('whatsapp'))->toBeTrue()
        ->and(NotificationSpy::sentVia('mail'))->toBeFalse();
});

it('rejects a model that is not an allowed notifiable with 422', function (): void {
    $type = makeDispatchType();

    $this->postJson("notification-center/admin/types/{$type->id}/dispatch", [
        'recipients' => [
            'model' => 'App\\Models\\NotAllowed',
            'ids' => [1],
        ],
    ])->assertStatus(422)->assertJsonValidationErrors(['recipients.model']);

    expect(NotificationSpy::$sent)->toBe([]);
});

it('rejects a channel outside the type supported channels with 422', function (): void {
    $type = makeDispatchType(['mail']);

    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test']);

    $this->postJson("notification-center/admin/types/{$type->id}/dispatch", [
        'recipients' => [
            'model' => User::class,
            'ids' => [$user->id],
        ],
        'channels' => ['whatsapp'],
    ])->assertStatus(422)->assertJsonValidationErrors(['channels.0']);
});

it('requires recipients', function (): void {
    $type = makeDispatchType();

    $this->postJson("notification-center/admin/types/{$type->id}/dispatch", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['recipients']);
});

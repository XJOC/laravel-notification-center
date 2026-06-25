<?php

declare(strict_types=1);

use Xjoc\NotificationCenter\Enums\CreatedBy;
use Xjoc\NotificationCenter\Enums\NotificationCategory;
use Xjoc\NotificationCenter\Facades\NotificationCenter;
use Xjoc\NotificationCenter\Models\NotificationType;
use Xjoc\NotificationCenter\Models\NotificationUserPreference;
use Xjoc\NotificationCenter\Tests\Fixtures\NotificationSpy;
use Xjoc\NotificationCenter\Tests\Fixtures\User;

/**
 * @param  array<int, string>  $channels
 */
function makePreferenceType(string $key, NotificationCategory $category, array $channels): NotificationType
{
    $type = NotificationType::query()->create([
        'key' => $key,
        'name' => ucfirst($key),
        'category' => $category,
        'supported_channels' => $channels,
        'variables' => ['order_id'],
        'is_locked' => $category === NotificationCategory::Essential,
        'is_enabled' => true,
        'created_by' => CreatedBy::Config,
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

it('returns the preference overview for the authenticated user', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test']);

    $order = makePreferenceType('order.confirmed', NotificationCategory::Transactional, ['mail', 'whatsapp']);
    $otp = makePreferenceType('otp.sent', NotificationCategory::Essential, ['whatsapp']);

    $response = $this->actingAs($user)->getJson('notification-center/user/preferences');

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                ['type_id', 'type_key', 'channel', 'opted_out', 'locked'],
            ],
        ]);

    $entries = $response->json('data');

    expect($entries)->toContain([
        'type_id' => $order->id,
        'type_key' => 'order.confirmed',
        'channel' => 'mail',
        'opted_out' => false,
        'locked' => false,
    ])->toContain([
        'type_id' => $otp->id,
        'type_key' => 'otp.sent',
        'channel' => 'whatsapp',
        'opted_out' => false,
        'locked' => true,
    ]);
});

it('persists an opt-out and blocks subsequent delivery on that channel', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test']);

    $type = makePreferenceType('order.confirmed', NotificationCategory::Transactional, ['mail', 'whatsapp']);

    $response = $this->actingAs($user)
        ->putJson("notification-center/user/preferences/{$type->id}/mail", [
            'opted_out' => true,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.opted_out', true)
        ->assertJsonPath('data.channel', 'mail')
        ->assertJsonPath('data.type_id', $type->id);

    expect(
        NotificationUserPreference::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->where('notification_type_id', $type->id)
            ->where('channel', 'mail')
            ->where('opted_out', true)
            ->exists()
    )->toBeTrue();

    NotificationSpy::reset();
    NotificationCenter::send('order.confirmed', $user, ['order_id' => '7']);

    expect(NotificationSpy::sentVia('mail'))->toBeFalse()
        ->and(NotificationSpy::sentVia('whatsapp'))->toBeTrue();
});

it('rejects changing preferences for an essential type with 403', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test']);

    $type = makePreferenceType('otp.sent', NotificationCategory::Essential, ['whatsapp']);

    $this->actingAs($user)
        ->putJson("notification-center/user/preferences/{$type->id}/whatsapp", [
            'opted_out' => true,
        ])
        ->assertStatus(403);

    expect(
        NotificationUserPreference::query()
            ->where('notification_type_id', $type->id)
            ->exists()
    )->toBeFalse();
});

it('validates that opted_out is required and boolean', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test']);

    $type = makePreferenceType('order.confirmed', NotificationCategory::Transactional, ['mail']);

    $this->actingAs($user)
        ->putJson("notification-center/user/preferences/{$type->id}/mail", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['opted_out']);
});

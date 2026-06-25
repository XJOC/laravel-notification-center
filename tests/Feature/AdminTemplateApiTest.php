<?php

declare(strict_types=1);

use XJOC\NotificationCenter\Enums\CreatedBy;
use XJOC\NotificationCenter\Enums\NotificationCategory;
use XJOC\NotificationCenter\Models\NotificationTemplate;
use XJOC\NotificationCenter\Models\NotificationType;

function makeTemplateType(): NotificationType
{
    $type = NotificationType::query()->create([
        'key' => 'order.confirmed',
        'name' => 'Order Confirmed',
        'category' => NotificationCategory::Transactional,
        'supported_channels' => ['mail', 'whatsapp'],
        'variables' => ['order_id'],
        'is_locked' => false,
        'is_enabled' => true,
        'created_by' => CreatedBy::Admin,
    ]);

    $type->settings()->create(['channel' => 'mail', 'is_enabled' => true]);
    $type->settings()->create(['channel' => 'whatsapp', 'is_enabled' => true]);

    return $type;
}

it('lists templates for a type', function (): void {
    $type = makeTemplateType();
    $type->templates()->create([
        'channel' => 'mail',
        'subject' => 'Order {{ order_id }}',
        'body' => 'Your order is confirmed.',
    ]);

    $response = $this->getJson("notification-center/admin/types/{$type->id}/templates");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.channel', 'mail')
        ->assertJsonPath('data.0.subject', 'Order {{ order_id }}')
        ->assertJsonPath('data.0.body', 'Your order is confirmed.');
});

it('creates a template on first upsert with 201', function (): void {
    $type = makeTemplateType();

    $response = $this->putJson("notification-center/admin/types/{$type->id}/templates/mail", [
        'subject' => 'Welcome {{ order_id }}',
        'body' => 'Hello there.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.channel', 'mail')
        ->assertJsonPath('data.subject', 'Welcome {{ order_id }}')
        ->assertJsonPath('data.body', 'Hello there.');

    expect(
        NotificationTemplate::query()
            ->where('notification_type_id', $type->id)
            ->where('channel', 'mail')
            ->count()
    )->toBe(1);
});

it('updates an existing template on subsequent upsert with 200', function (): void {
    $type = makeTemplateType();

    $this->putJson("notification-center/admin/types/{$type->id}/templates/mail", [
        'subject' => 'Original',
        'body' => 'Original body.',
    ])->assertCreated();

    $response = $this->putJson("notification-center/admin/types/{$type->id}/templates/mail", [
        'subject' => 'Updated',
        'body' => 'Updated body.',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.subject', 'Updated')
        ->assertJsonPath('data.body', 'Updated body.');

    expect(
        NotificationTemplate::query()
            ->where('notification_type_id', $type->id)
            ->where('channel', 'mail')
            ->count()
    )->toBe(1);
});

it('allows a null subject for non-mail channels', function (): void {
    $type = makeTemplateType();

    $response = $this->putJson("notification-center/admin/types/{$type->id}/templates/whatsapp", [
        'body' => 'Your order shipped.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.subject', null)
        ->assertJsonPath('data.body', 'Your order shipped.');
});

it('validates template body is required', function (): void {
    $type = makeTemplateType();

    $this->putJson("notification-center/admin/types/{$type->id}/templates/mail", [
        'subject' => 'No body',
    ])->assertStatus(422)->assertJsonValidationErrors(['body']);
});

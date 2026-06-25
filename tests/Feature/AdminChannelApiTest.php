<?php

declare(strict_types=1);

use XJOC\NotificationCenter\Channels\ChannelRegistry;
use XJOC\NotificationCenter\Tests\Fixtures\FakeChannel;

it('returns exactly the registered channel keys', function (): void {
    $response = $this->getJson('notification-center/admin/channels');

    $response->assertOk();
    expect($response->json('data'))->toBe(['mail', 'database', 'whatsapp']);
});

it('reflects a channel the developer registered at runtime', function (): void {
    app(ChannelRegistry::class)->register('telegram', FakeChannel::class);

    $data = $this->getJson('notification-center/admin/channels')->assertOk()->json('data');

    expect($data)->toContain('telegram');
});

it('is read-only and rejects write methods', function (): void {
    $this->postJson('notification-center/admin/channels')->assertStatus(405);
    $this->putJson('notification-center/admin/channels')->assertStatus(405);
    $this->deleteJson('notification-center/admin/channels')->assertStatus(405);
});

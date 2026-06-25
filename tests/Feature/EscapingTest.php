<?php

declare(strict_types=1);

use Illuminate\Notifications\Messages\MailMessage;
use Xjoc\NotificationCenter\Facades\NotificationCenter;
use Xjoc\NotificationCenter\Models\NotificationSetting;
use Xjoc\NotificationCenter\Models\NotificationTemplate;
use Xjoc\NotificationCenter\Models\NotificationType;
use Xjoc\NotificationCenter\Tests\Fixtures\NotificationSpy;
use Xjoc\NotificationCenter\Tests\Fixtures\User;

/**
 * Creates a type supporting mail, database, and whatsapp, with a template whose
 * subject and body both render the {{ value }} escaped token, so each channel
 * driver's own escaping decision can be exercised end-to-end. The gateway injects
 * the raw template; the per-channel driver (e.g. MailChannel / WhatsappChannel)
 * decides whether to escape.
 */
function escapingMakeType(): NotificationType
{
    /** @var NotificationType $type */
    $type = NotificationType::query()->create([
        'key' => 'order.confirmed',
        'name' => 'Order Confirmed',
        'category' => 'transactional',
        'supported_channels' => ['mail', 'database', 'whatsapp'],
        'variables' => ['value'],
        'is_locked' => false,
        'is_enabled' => true,
        'created_by' => 'config',
    ]);

    foreach (['mail', 'database', 'whatsapp'] as $channel) {
        NotificationSetting::query()->create([
            'notification_type_id' => $type->id,
            'channel' => $channel,
            'is_enabled' => true,
        ]);

        NotificationTemplate::query()->create([
            'notification_type_id' => $type->id,
            'channel' => $channel,
            'subject' => 'Subject {{ value }}',
            'body' => 'Body {{ value }}',
        ]);
    }

    return $type;
}

it('escapes variable values on html channels (mail) but leaves them raw on non-html channels (whatsapp)', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    escapingMakeType();

    NotificationCenter::send('order.confirmed', $user, ['value' => '<b>x</b>']);

    /** @var MailMessage $mail */
    $mail = NotificationSpy::forChannel('mail')[0]['payload'];
    expect($mail->introLines)->toContain('Body '.e('<b>x</b>'));
    expect($mail->introLines)->not->toContain('Body <b>x</b>');

    /** @var string $whatsapp */
    $whatsapp = NotificationSpy::forChannel('whatsapp')[0]['payload'];
    expect($whatsapp)->toBe('Body <b>x</b>');

    // The database channel is not HTML, so its driver leaves the value raw too.
    /** @var array{subject: ?string, body: string} $database */
    $database = NotificationSpy::forChannel('database')[0]['payload'];
    expect($database['body'])->toBe('Body <b>x</b>');
});

it('renders the mail subject raw because the mail driver never escapes subjects', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    escapingMakeType();

    NotificationCenter::send('order.confirmed', $user, ['value' => '<b>x</b>'], ['mail']);

    /** @var MailMessage $mail */
    $mail = NotificationSpy::forChannel('mail')[0]['payload'];
    expect($mail->subject)->toBe('Subject <b>x</b>');
});

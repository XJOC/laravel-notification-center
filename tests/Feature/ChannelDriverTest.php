<?php

declare(strict_types=1);

use Illuminate\Notifications\Messages\MailMessage;
use Xjoc\NotificationCenter\Channels\DatabaseChannel;
use Xjoc\NotificationCenter\Channels\MailChannel;
use Xjoc\NotificationCenter\Channels\WhatsappChannel;
use Xjoc\NotificationCenter\Templates\ChannelTemplate;
use Xjoc\NotificationCenter\Tests\Fixtures\User;

it('mail driver renders the subject raw and the body HTML-escaped into a MailMessage', function (): void {
    $message = app(MailChannel::class)->render(
        new ChannelTemplate('Hi {{ name }}', 'Body {{ value }}'),
        ['name' => 'Sam', 'value' => '<b>x</b>'],
        new User,
    );

    expect($message)->toBeInstanceOf(MailMessage::class)
        ->and($message->subject)->toBe('Hi Sam')
        ->and($message->introLines)->toContain('Body '.e('<b>x</b>'))
        ->and($message->introLines)->not->toContain('Body <b>x</b>');
});

it('database driver renders subject and body raw into an array', function (): void {
    $payload = app(DatabaseChannel::class)->render(
        new ChannelTemplate('Subj {{ value }}', 'Body {{ value }}'),
        ['value' => '<b>x</b>'],
        new User,
    );

    expect($payload)->toBe(['subject' => 'Subj <b>x</b>', 'body' => 'Body <b>x</b>']);
});

it('whatsapp driver renders the body raw as a string', function (): void {
    $payload = app(WhatsappChannel::class)->render(
        new ChannelTemplate(null, 'Body {{ value }}'),
        ['value' => '<b>x</b>'],
        new User,
    );

    expect($payload)->toBe('Body <b>x</b>');
});

it('each built-in driver reports its own channel key', function (): void {
    expect(app(MailChannel::class)->key())->toBe('mail')
        ->and(app(DatabaseChannel::class)->key())->toBe('database')
        ->and(app(WhatsappChannel::class)->key())->toBe('whatsapp');
});

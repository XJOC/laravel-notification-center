<?php

declare(strict_types=1);

use Illuminate\Notifications\Messages\MailMessage;
use XJOC\NotificationCenter\Exceptions\MissingTemplateException;
use XJOC\NotificationCenter\Tests\Fixtures\CustomMailNotification;
use XJOC\NotificationCenter\Tests\Fixtures\OrderConfirmedNotification;
use XJOC\NotificationCenter\Tests\Fixtures\User;

it('builds a mail message with subject and body from the injected template', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    $notification = new OrderConfirmedNotification;
    $notification->injectTemplate('mail', 'Body text', 'Subject line');

    $message = $notification->toMail($user);

    expect($message)->toBeInstanceOf(MailMessage::class);
    expect($message->subject)->toBe('Subject line');
    expect($message->introLines)->toContain('Body text');
});

it('builds a mail message without a subject when none is injected', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    $notification = new OrderConfirmedNotification;
    $notification->injectTemplate('mail', 'Body only');

    $message = $notification->toMail($user);

    expect($message->subject)->toBeNull();
    expect($message->introLines)->toContain('Body only');
});

it('builds a database payload array from the injected template', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    $notification = new OrderConfirmedNotification;
    $notification->injectTemplate('database', 'Stored body', 'Stored subject');

    $payload = $notification->toDatabase($user);

    expect($payload)->toBe(['subject' => 'Stored subject', 'body' => 'Stored body']);
    expect($notification->toArray($user))->toBe($payload);
});

it('builds a whatsapp string from the injected template body', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    $notification = new OrderConfirmedNotification;
    $notification->injectTemplate('whatsapp', 'WhatsApp body', 'ignored subject');

    expect($notification->toWhatsapp($user))->toBe('WhatsApp body');
});

it('lets a developer override toMail win over the injected template', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    $notification = new CustomMailNotification;
    $notification->injectTemplate('mail', 'Injected body', 'Injected subject');

    $message = $notification->toMail($user);

    expect($message)->toBeInstanceOf(MailMessage::class);
    expect($message->subject)->not->toBe('Injected subject');
});

it('throws MissingTemplateException when the mail channel has no injected template', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    $notification = new OrderConfirmedNotification;

    expect(fn (): MailMessage => $notification->toMail($user))
        ->toThrow(MissingTemplateException::class);
});

it('throws MissingTemplateException when the whatsapp channel has no injected template', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    $notification = new OrderConfirmedNotification;

    expect(fn (): string => $notification->toWhatsapp($user))
        ->toThrow(MissingTemplateException::class);
});

it('throws MissingTemplateException when the database channel has no injected template', function (): void {
    /** @var User $user */
    $user = User::query()->create(['name' => 'Sam', 'email' => 'sam@example.test', 'password' => 'secret']);

    $notification = new OrderConfirmedNotification;

    expect(fn (): array => $notification->toDatabase($user))
        ->toThrow(MissingTemplateException::class);
});

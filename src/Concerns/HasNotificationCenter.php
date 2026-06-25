<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Concerns;

use Illuminate\Notifications\Messages\MailMessage;
use Xjoc\NotificationCenter\Enums\Channel;
use Xjoc\NotificationCenter\Exceptions\MissingTemplateException;
use Xjoc\NotificationCenter\Support\NotificationCenterCache;

trait HasNotificationCenter
{
    /** @var array<string, array{subject: ?string, body: string}> */
    protected array $injectedTemplates = [];

    abstract public function notificationType(): string;

    public function injectTemplate(string $channel, string $rendered, ?string $subject = null): void
    {
        $this->injectedTemplates[$channel] = ['subject' => $subject, 'body' => $rendered];
    }

    /** @return array{subject: ?string, body: string}|null */
    protected function injectedTemplate(string $channel): ?array
    {
        return $this->injectedTemplates[$channel] ?? null;
    }

    /** @return array<string, mixed> */
    public function notificationVariables(object $notifiable): array
    {
        return [];
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return app(NotificationCenterCache::class)->supportedChannels($this->notificationType());
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = $this->injectedTemplate(Channel::Mail->value)
            ?? throw MissingTemplateException::forChannel($this->notificationType(), Channel::Mail->value);

        $message = new MailMessage;

        if ($template['subject'] !== null) {
            $message->subject($template['subject']);
        }

        return $message->line($template['body']);
    }

    /** @return array{subject: ?string, body: string} */
    public function toDatabase(object $notifiable): array
    {
        return $this->injectedTemplate(Channel::Database->value)
            ?? throw MissingTemplateException::forChannel($this->notificationType(), Channel::Database->value);
    }

    /** @return array{subject: ?string, body: string} */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toWhatsapp(object $notifiable): string
    {
        $template = $this->injectedTemplate(Channel::Whatsapp->value)
            ?? throw MissingTemplateException::forChannel($this->notificationType(), Channel::Whatsapp->value);

        return $template['body'];
    }
}

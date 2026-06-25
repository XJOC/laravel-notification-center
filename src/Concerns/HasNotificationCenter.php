<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Concerns;

use Illuminate\Notifications\Messages\MailMessage;
use LogicException;
use XJOC\NotificationCenter\Channels\ChannelRegistry;
use XJOC\NotificationCenter\Enums\Channel;
use XJOC\NotificationCenter\Exceptions\MissingTemplateException;
use XJOC\NotificationCenter\Support\NotificationCenterCache;
use XJOC\NotificationCenter\Templates\ChannelTemplate;

trait HasNotificationCenter
{
    /**
     * Raw (un-rendered) templates injected by the gateway, keyed by channel.
     *
     * @var array<string, array{subject: ?string, body: string}>
     */
    protected array $injectedTemplates = [];

    abstract public function notificationType(): string;

    public function injectTemplate(string $channel, string $rendered, ?string $subject = null): void
    {
        $this->injectedTemplates[$channel] = ['subject' => $subject, 'body' => $rendered];
    }

    /**
     * @return array<string, mixed>
     */
    public function notificationVariables(object $notifiable): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return app(NotificationCenterCache::class)->supportedChannels($this->notificationType());
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->renderChannel(Channel::Mail->value, $notifiable);

        if (! $payload instanceof MailMessage) {
            throw new LogicException('The [mail] channel driver must return a MailMessage instance.');
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $payload = $this->renderChannel(Channel::Database->value, $notifiable);

        if (! is_array($payload)) {
            throw new LogicException('The [database] channel driver must return an array.');
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toWhatsapp(object $notifiable): string
    {
        $payload = $this->renderChannel(Channel::Whatsapp->value, $notifiable);

        if (! is_string($payload)) {
            throw new LogicException('The [whatsapp] channel driver must return a string.');
        }

        return $payload;
    }

    /**
     * Resolve the channel driver and let it render the injected template. A
     * developer who overrides a channel method (e.g. toMail) bypasses this
     * entirely — their method wins and the injected template is ignored.
     */
    private function renderChannel(string $channel, object $notifiable): mixed
    {
        $template = $this->injectedTemplates[$channel]
            ?? throw MissingTemplateException::forChannel($this->notificationType(), $channel);

        return app(ChannelRegistry::class)->driver($channel)->render(
            new ChannelTemplate($template['subject'], $template['body']),
            $this->notificationVariables($notifiable),
            $notifiable,
        );
    }
}

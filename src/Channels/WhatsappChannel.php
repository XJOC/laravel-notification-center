<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Channels;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Notifications\Notification;
use XJOC\NotificationCenter\Contracts\NotificationChannel;
use XJOC\NotificationCenter\Contracts\WhatsappTransport;
use XJOC\NotificationCenter\Enums\Channel;
use XJOC\NotificationCenter\Templates\ChannelTemplate;
use XJOC\NotificationCenter\Templates\TemplateRenderer;

/**
 * WhatsApp driver. Two responsibilities:
 *  - render(): renders the template body as raw text (no HTML escaping).
 *  - send(): the single fixed delivery entry point — renders to text, wraps it
 *    in a structured WhatsappMessage, and hands it to the developer's transport.
 *
 * The package never talks to a provider API itself; the bound WhatsappTransport
 * (developer-supplied) performs delivery.
 */
final class WhatsappChannel implements NotificationChannel
{
    public function __construct(
        private TemplateRenderer $renderer,
        private ConfigRepository $config,
        private WhatsappTransport $transport,
    ) {}

    public function key(): string
    {
        return Channel::Whatsapp->value;
    }

    public function render(ChannelTemplate $template, array $variables, object $notifiable): string
    {
        return $this->renderer->render($template->body, $variables, false, $this->onMissing());
    }

    /**
     * Laravel notification channel entry point. Renders to text (honoring a
     * developer's toWhatsapp() override), builds a WhatsappMessage::text, and
     * delegates delivery to the bound transport.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $to = $this->routeFor($notifiable);

        if ($to === null) {
            return;
        }

        if (! method_exists($notification, 'toWhatsapp')) {
            return;
        }

        $body = $notification->toWhatsapp($notifiable);

        if (! is_string($body)) {
            return;
        }

        $this->transport->send(WhatsappMessage::text($to, $body));
    }

    private function routeFor(object $notifiable): ?string
    {
        if (! method_exists($notifiable, 'routeNotificationFor')) {
            return null;
        }

        $route = $notifiable->routeNotificationFor('whatsapp', null);

        return is_string($route) && $route !== '' ? $route : null;
    }

    private function onMissing(): string
    {
        $value = $this->config->get('notification-center.templates.on_missing_var', 'empty');

        return is_string($value) ? $value : 'empty';
    }
}

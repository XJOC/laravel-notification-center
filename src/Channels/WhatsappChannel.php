<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Channels;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Xjoc\NotificationCenter\Contracts\NotificationChannel;
use Xjoc\NotificationCenter\Enums\Channel;
use Xjoc\NotificationCenter\Templates\ChannelTemplate;
use Xjoc\NotificationCenter\Templates\TemplateRenderer;

/**
 * WhatsApp driver: renders the body as raw text (no HTML escaping) and returns
 * the message string. Actual delivery is handled by a host-provided transport
 * registered for the "whatsapp" channel.
 */
final class WhatsappChannel implements NotificationChannel
{
    public function __construct(
        private TemplateRenderer $renderer,
        private ConfigRepository $config,
    ) {}

    public function key(): string
    {
        return Channel::Whatsapp->value;
    }

    public function render(ChannelTemplate $template, array $variables, object $notifiable): string
    {
        return $this->renderer->render($template->body, $variables, false, $this->onMissing());
    }

    private function onMissing(): string
    {
        $value = $this->config->get('notification-center.templates.on_missing_var', 'empty');

        return is_string($value) ? $value : 'empty';
    }
}

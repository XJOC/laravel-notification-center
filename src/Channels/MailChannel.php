<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Channels;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Notifications\Messages\MailMessage;
use XJOC\NotificationCenter\Contracts\NotificationChannel;
use XJOC\NotificationCenter\Enums\Channel;
use XJOC\NotificationCenter\Templates\ChannelTemplate;
use XJOC\NotificationCenter\Templates\TemplateRenderer;

/**
 * Mail driver: renders the subject as plain text and the body with HTML escaping
 * (variable values are escaped) and builds a MailMessage.
 */
final class MailChannel implements NotificationChannel
{
    public function __construct(
        private TemplateRenderer $renderer,
        private ConfigRepository $config,
    ) {}

    public function key(): string
    {
        return Channel::Mail->value;
    }

    public function render(ChannelTemplate $template, array $variables, object $notifiable): MailMessage
    {
        $onMissing = $this->onMissing();

        $message = new MailMessage;

        if ($template->subject !== null) {
            $message->subject($this->renderer->render($template->subject, $variables, false, $onMissing));
        }

        return $message->line(
            $this->renderer->render($template->body, $variables, $this->shouldEscape(), $onMissing)
        );
    }

    private function shouldEscape(): bool
    {
        return (bool) $this->config->get('notification-center.templates.escape_html', true);
    }

    private function onMissing(): string
    {
        $value = $this->config->get('notification-center.templates.on_missing_var', 'empty');

        return is_string($value) ? $value : 'empty';
    }
}

<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Channels;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use XJOC\NotificationCenter\Contracts\NotificationChannel;
use XJOC\NotificationCenter\Enums\Channel;
use XJOC\NotificationCenter\Templates\ChannelTemplate;
use XJOC\NotificationCenter\Templates\TemplateRenderer;

/**
 * Database driver: renders subject + body as raw text (the stored payload is
 * structured data, not HTML) and returns the array persisted by Laravel's
 * database notification channel.
 */
final class DatabaseChannel implements NotificationChannel
{
    public function __construct(
        private TemplateRenderer $renderer,
        private ConfigRepository $config,
    ) {}

    public function key(): string
    {
        return Channel::Database->value;
    }

    /**
     * @return array{subject: ?string, body: string}
     */
    public function render(ChannelTemplate $template, array $variables, object $notifiable): array
    {
        $onMissing = $this->onMissing();

        return [
            'subject' => $template->subject !== null
                ? $this->renderer->render($template->subject, $variables, false, $onMissing)
                : null,
            'body' => $this->renderer->render($template->body, $variables, false, $onMissing),
        ];
    }

    private function onMissing(): string
    {
        $value = $this->config->get('notification-center.templates.on_missing_var', 'empty');

        return is_string($value) ? $value : 'empty';
    }
}

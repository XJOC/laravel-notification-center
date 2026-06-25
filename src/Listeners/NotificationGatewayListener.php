<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Listeners;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Notifications\Events\NotificationSending;
use Xjoc\NotificationCenter\Contracts\NotifiableNotification;
use Xjoc\NotificationCenter\Models\NotificationType;
use Xjoc\NotificationCenter\Support\NotificationCenterCache;
use Xjoc\NotificationCenter\Support\PreferenceResolver;
use Xjoc\NotificationCenter\Templates\TemplateRenderer;

final class NotificationGatewayListener
{
    public function __construct(
        private NotificationCenterCache $cache,
        private PreferenceResolver $preferences,
        private TemplateRenderer $renderer,
        private ConfigRepository $config,
    ) {}

    public function handle(NotificationSending $event): ?bool
    {
        $notification = $event->notification;

        if (! $notification instanceof NotifiableNotification) {
            return null;
        }

        $type = $this->cache->type($notification->notificationType());

        if ($type === null) {
            return null;
        }

        $notifiable = $event->notifiable;

        if (! is_object($notifiable)) {
            return null;
        }

        $channel = $event->channel;

        if ($type->category->bypassesGateway()) {
            $this->inject($type, $notification, $notifiable, $channel);

            return null;
        }

        if (! $type->is_enabled) {
            return false;
        }

        if (! $this->cache->settingEnabled($type->id, $channel)) {
            return false;
        }

        if ($this->preferences->optedOut($notifiable, $type->id, $channel)) {
            return false;
        }

        $this->inject($type, $notification, $notifiable, $channel);

        return null;
    }

    private function inject(NotificationType $type, NotifiableNotification $notification, object $notifiable, string $channel): void
    {
        $template = $this->cache->template($type->id, $channel);

        if ($template === null) {
            return;
        }

        $variables = $notification->notificationVariables($notifiable);

        $escape = (bool) $this->config->get('notification-center.templates.escape_html')
            && in_array($channel, (array) $this->config->get('notification-center.templates.html_channels'), true);

        $onMissingValue = $this->config->get('notification-center.templates.on_missing_var');
        $onMissing = is_string($onMissingValue) ? $onMissingValue : 'empty';

        $subject = $template->subject !== null
            ? $this->renderer->render($template->subject, $variables, false, $onMissing)
            : null;

        $body = $this->renderer->render($template->body, $variables, $escape, $onMissing);

        $notification->injectTemplate($channel, $body, $subject);
    }
}

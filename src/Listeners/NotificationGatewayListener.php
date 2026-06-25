<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Listeners;

use Illuminate\Notifications\Events\NotificationSending;
use XJOC\NotificationCenter\Contracts\NotifiableNotification;
use XJOC\NotificationCenter\Models\NotificationType;
use XJOC\NotificationCenter\Support\NotificationCenterCache;
use XJOC\NotificationCenter\Support\PreferenceResolver;

final class NotificationGatewayListener
{
    public function __construct(
        private NotificationCenterCache $cache,
        private PreferenceResolver $preferences,
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
            $this->inject($type, $notification, $channel);

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

        $this->inject($type, $notification, $channel);

        return null;
    }

    /**
     * Hand the raw (un-rendered) template to the notification. The channel driver
     * renders it — with its own format and escaping — at delivery time.
     */
    private function inject(NotificationType $type, NotifiableNotification $notification, string $channel): void
    {
        $template = $this->cache->template($type->id, $channel);

        if ($template === null) {
            return;
        }

        $notification->injectTemplate($channel, $template->body, $template->subject);
    }
}

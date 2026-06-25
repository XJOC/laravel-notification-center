<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Listeners;

use Xjoc\NotificationCenter\Contracts\ProvidesNotificationContext;
use Xjoc\NotificationCenter\NotificationCenterManager;
use Xjoc\NotificationCenter\Support\NotificationCenterCache;

final class EventBindingListener
{
    public function __construct(
        private NotificationCenterCache $cache,
        private NotificationCenterManager $manager,
    ) {}

    public function handle(object $event): void
    {
        if (! $event instanceof ProvidesNotificationContext) {
            return;
        }

        $typeKeys = $this->cache->eventBindings()[$event::class] ?? [];

        if ($typeKeys === []) {
            return;
        }

        $recipients = $event->notificationRecipients();
        $variables = $event->notificationVariables();

        foreach ($typeKeys as $typeKey) {
            $this->manager->send($typeKey, $recipients, $variables);
        }
    }
}

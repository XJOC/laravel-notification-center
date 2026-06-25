<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Tests\Fixtures;

use XJOC\NotificationCenter\Contracts\ProvidesNotificationContext;

/**
 * A host domain event that carries notification context. Used by the event
 * binding suites as a real, existing class for the `class_exists` validation
 * rule on event bindings.
 */
final class OrderShippedEvent implements ProvidesNotificationContext
{
    /**
     * @param  iterable<int, object>  $recipients
     * @param  array<string, mixed>  $variables
     */
    public function __construct(
        private iterable $recipients = [],
        private array $variables = [],
    ) {}

    /**
     * @return iterable<int, object>
     */
    public function notificationRecipients(): iterable
    {
        return $this->recipients;
    }

    /**
     * @return array<string, mixed>
     */
    public function notificationVariables(): array
    {
        return $this->variables;
    }
}

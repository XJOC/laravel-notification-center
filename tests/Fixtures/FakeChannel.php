<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Tests\Fixtures;

use Xjoc\NotificationCenter\Contracts\NotificationChannel;
use Xjoc\NotificationCenter\Templates\ChannelTemplate;

/**
 * A minimal custom channel driver used to prove a developer can register their
 * own self-contained channel (one class, no edits to a central renderer).
 */
final class FakeChannel implements NotificationChannel
{
    public function key(): string
    {
        return 'fake';
    }

    public function render(ChannelTemplate $template, array $variables, object $notifiable): string
    {
        return 'FAKE:'.$template->body;
    }
}

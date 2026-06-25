<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Contracts;

use XJOC\NotificationCenter\Templates\ChannelTemplate;

/**
 * A channel driver owns the rendering of its OWN template format: it receives
 * the raw template (subject + body) plus the per-recipient variables and turns
 * them into the payload its transport understands. Each channel decides its own
 * format and escaping (e.g. mail escapes HTML, whatsapp stays raw), so adding a
 * new channel never requires editing a central renderer.
 */
interface NotificationChannel
{
    /**
     * The channel key this driver is registered under (e.g. "mail", "whatsapp").
     */
    public function key(): string;

    /**
     * Render the raw template + variables into this channel's delivery payload.
     *
     * @param  array<string, mixed>  $variables
     */
    public function render(ChannelTemplate $template, array $variables, object $notifiable): mixed;
}

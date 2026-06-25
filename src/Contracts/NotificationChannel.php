<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Contracts;

interface NotificationChannel
{
    public function channel(): string;

    /**
     * @param  array{subject: ?string, body: string}  $template
     */
    public function build(object $notifiable, object $notification, array $template): mixed;
}

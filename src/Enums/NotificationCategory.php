<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Enums;

enum NotificationCategory: string
{
    case Essential = 'essential';
    case Transactional = 'transactional';
    case Alerts = 'alerts';
    case Marketing = 'marketing';

    public function bypassesGateway(): bool
    {
        return $this === self::Essential;
    }

    public function forcesLock(): bool
    {
        return $this === self::Essential;
    }
}

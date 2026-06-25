<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Enums;

enum Channel: string
{
    case Mail = 'mail';
    case Database = 'database';
    case Whatsapp = 'whatsapp';
}

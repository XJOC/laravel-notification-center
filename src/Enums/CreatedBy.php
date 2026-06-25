<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Enums;

enum CreatedBy: string
{
    case Config = 'config';
    case Admin = 'admin';
}

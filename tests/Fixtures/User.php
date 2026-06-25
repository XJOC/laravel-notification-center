<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 */
final class User extends Authenticatable
{
    use Notifiable;

    /** @var list<string> */
    protected $guarded = [];

    /** @var string */
    protected $table = 'users';

    public function routeNotificationForWhatsapp(mixed $notification = null): string
    {
        return '+15555550123';
    }
}

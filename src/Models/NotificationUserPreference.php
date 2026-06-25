<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $notification_type_id
 * @property string $notifiable_type
 * @property int|string $notifiable_id
 * @property string $channel
 * @property bool $opted_out
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class NotificationUserPreference extends Model
{
    /** @var array<int, string> */
    protected $guarded = [];

    /**
     * @return MorphTo<Model, $this>
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<NotificationType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class, 'notification_type_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opted_out' => 'boolean',
        ];
    }
}

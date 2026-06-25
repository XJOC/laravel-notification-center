<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $notification_type_id
 * @property string $channel
 * @property bool $is_enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class NotificationSetting extends Model
{
    /** @var array<int, string> */
    protected $guarded = [];

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
            'is_enabled' => 'boolean',
        ];
    }
}

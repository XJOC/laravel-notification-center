<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use XJOC\NotificationCenter\Enums\CreatedBy;
use XJOC\NotificationCenter\Enums\NotificationCategory;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property NotificationCategory $category
 * @property array<int, string> $supported_channels
 * @property array<int, string>|null $variables
 * @property bool $is_locked
 * @property bool $is_enabled
 * @property CreatedBy $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class NotificationType extends Model
{
    /** @var array<int, string> */
    protected $guarded = [];

    /**
     * @return HasMany<NotificationSetting, $this>
     */
    public function settings(): HasMany
    {
        return $this->hasMany(NotificationSetting::class);
    }

    /**
     * @return HasMany<NotificationTemplate, $this>
     */
    public function templates(): HasMany
    {
        return $this->hasMany(NotificationTemplate::class);
    }

    /**
     * @return HasMany<NotificationUserPreference, $this>
     */
    public function preferences(): HasMany
    {
        return $this->hasMany(NotificationUserPreference::class);
    }

    /**
     * @return HasMany<NotificationEventBinding, $this>
     */
    public function eventBindings(): HasMany
    {
        return $this->hasMany(NotificationEventBinding::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => NotificationCategory::class,
            'created_by' => CreatedBy::class,
            'supported_channels' => 'array',
            'variables' => 'array',
            'is_locked' => 'boolean',
            'is_enabled' => 'boolean',
        ];
    }
}

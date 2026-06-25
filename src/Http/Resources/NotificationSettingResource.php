<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use XJOC\NotificationCenter\Models\NotificationSetting;

/**
 * @mixin NotificationSetting
 */
final class NotificationSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'notification_type_id' => $this->notification_type_id,
            'channel' => $this->channel,
            'is_enabled' => $this->is_enabled,
        ];
    }
}

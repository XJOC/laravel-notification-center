<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use XJOC\NotificationCenter\Models\NotificationUserPreference;

/**
 * @mixin NotificationUserPreference
 */
final class NotificationUserPreferenceResource extends JsonResource
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
            'opted_out' => $this->opted_out,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Xjoc\NotificationCenter\Models\NotificationType;

/**
 * @mixin NotificationType
 */
final class NotificationTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'category' => $this->category->value,
            'supported_channels' => $this->supported_channels,
            'variables' => $this->variables,
            'is_locked' => $this->is_locked,
            'is_enabled' => $this->is_enabled,
            'created_by' => $this->created_by->value,
            'settings' => NotificationSettingResource::collection($this->whenLoaded('settings')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

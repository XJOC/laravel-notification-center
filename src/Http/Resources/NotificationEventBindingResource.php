<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Xjoc\NotificationCenter\Models\NotificationEventBinding;

/**
 * @mixin NotificationEventBinding
 */
final class NotificationEventBindingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'notification_type_id' => $this->notification_type_id,
            'event_class' => $this->event_class,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

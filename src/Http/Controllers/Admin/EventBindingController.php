<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Xjoc\NotificationCenter\Http\Requests\StoreEventBindingRequest;
use Xjoc\NotificationCenter\Http\Resources\NotificationEventBindingResource;
use Xjoc\NotificationCenter\Models\NotificationEventBinding;
use Xjoc\NotificationCenter\Models\NotificationType;
use Xjoc\NotificationCenter\Support\NotificationCenterCache;

final class EventBindingController extends Controller
{
    public function __construct(private NotificationCenterCache $cache) {}

    public function index(NotificationType $type): JsonResource
    {
        return NotificationEventBindingResource::collection($type->eventBindings);
    }

    public function store(StoreEventBindingRequest $request, NotificationType $type): JsonResponse
    {
        /** @var array{event_class: string, is_active?: bool} $data */
        $data = $request->validated();

        $binding = new NotificationEventBinding;
        $binding->notification_type_id = $type->id;
        $binding->event_class = $data['event_class'];
        $binding->is_active = $data['is_active'] ?? true;
        $binding->save();

        $this->cache->forgetEventBindings();

        return (new NotificationEventBindingResource($binding))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(NotificationEventBinding $binding): JsonResponse
    {
        $binding->delete();

        $this->cache->forgetEventBindings();

        return new JsonResponse(null, 204);
    }
}

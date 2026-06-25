<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use XJOC\NotificationCenter\Enums\CreatedBy;
use XJOC\NotificationCenter\Enums\NotificationCategory;
use XJOC\NotificationCenter\Http\Requests\StoreTypeRequest;
use XJOC\NotificationCenter\Http\Requests\UpdateTypeRequest;
use XJOC\NotificationCenter\Http\Resources\NotificationTypeResource;
use XJOC\NotificationCenter\Models\NotificationType;
use XJOC\NotificationCenter\Support\NotificationCenterCache;

final class TypeController extends Controller
{
    public function __construct(private NotificationCenterCache $cache) {}

    public function index(): JsonResource
    {
        $types = NotificationType::query()->with('settings')->get();

        return NotificationTypeResource::collection($types);
    }

    public function store(StoreTypeRequest $request): JsonResponse
    {
        /** @var array{key: string, name: string, category: string, channels: array<int, string>, locked?: bool, variables?: array<int, string>} $data */
        $data = $request->validated();

        $category = NotificationCategory::from($data['category']);
        $essential = $category->forcesLock();

        /** @var array<int, string> $channels */
        $channels = $data['channels'];

        $type = new NotificationType;
        $type->key = $data['key'];
        $type->name = $data['name'];
        $type->category = $category;
        $type->supported_channels = $channels;
        $type->variables = $data['variables'] ?? [];
        $type->is_locked = $essential ? true : ($data['locked'] ?? false);
        $type->is_enabled = true;
        $type->created_by = CreatedBy::Admin;
        $type->save();

        foreach ($channels as $channel) {
            $type->settings()->create([
                'channel' => $channel,
                'is_enabled' => true,
            ]);
        }

        $this->cache->forgetType($type->key);
        $this->cache->forgetSettings($type->id);
        $this->cache->forgetEventBindings();

        $type->load('settings');

        return (new NotificationTypeResource($type))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTypeRequest $request, NotificationType $type): NotificationTypeResource
    {
        /** @var array{name?: string, supported_channels?: array<int, string>, is_enabled?: bool} $data */
        $data = $request->validated();

        if (array_key_exists('name', $data)) {
            $type->name = $data['name'];
        }

        if (array_key_exists('is_enabled', $data)) {
            $type->is_enabled = $data['is_enabled'];
        }

        $previousChannels = $type->supported_channels;

        if (array_key_exists('supported_channels', $data)) {
            /** @var array<int, string> $channels */
            $channels = $data['supported_channels'];
            $type->supported_channels = $channels;
        }

        $type->save();

        $currentChannels = $type->supported_channels;
        $newChannels = array_values(array_diff($currentChannels, $previousChannels));

        foreach ($newChannels as $channel) {
            $type->settings()->firstOrCreate(
                ['channel' => $channel],
                ['is_enabled' => true],
            );
        }

        $this->cache->forgetType($type->key);
        $this->cache->forgetSettings($type->id);
        $this->cache->forgetEventBindings();

        $type->load('settings');

        return new NotificationTypeResource($type);
    }
}

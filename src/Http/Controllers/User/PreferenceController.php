<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Http\Controllers\User;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Xjoc\NotificationCenter\Enums\NotificationCategory;
use Xjoc\NotificationCenter\Http\Requests\UpdatePreferenceRequest;
use Xjoc\NotificationCenter\Models\NotificationType;
use Xjoc\NotificationCenter\Models\NotificationUserPreference;
use Xjoc\NotificationCenter\Support\PreferenceResolver;

final class PreferenceController extends Controller
{
    public function __construct(private readonly PreferenceResolver $preferences) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user instanceof Model, 401);

        /** @var Collection<int, NotificationType> $types */
        $types = NotificationType::query()->where('is_enabled', true)->get();

        /** @var array<int, array{type_id: int, type_key: string, channel: string, opted_out: bool, locked: bool}> $data */
        $data = [];

        foreach ($types as $type) {
            $locked = $type->category === NotificationCategory::Essential;

            foreach ($type->supported_channels as $channel) {
                $data[] = [
                    'type_id' => $type->id,
                    'type_key' => $type->key,
                    'channel' => $channel,
                    'opted_out' => $this->preferences->optedOut($user, $type->id, $channel),
                    'locked' => $locked,
                ];
            }
        }

        return new JsonResponse(['data' => $data]);
    }

    public function update(UpdatePreferenceRequest $request, NotificationType $type, string $channel): JsonResponse
    {
        if ($type->category === NotificationCategory::Essential) {
            abort(403, 'Essential notifications cannot be changed.');
        }

        $user = $request->user();
        abort_if(! $user instanceof Model, 401);

        $optedOut = $request->boolean('opted_out');

        NotificationUserPreference::query()->updateOrCreate(
            [
                'notifiable_type' => $user->getMorphClass(),
                'notifiable_id' => $user->getKey(),
                'notification_type_id' => $type->id,
                'channel' => $channel,
            ],
            ['opted_out' => $optedOut],
        );

        $this->preferences->forget($user, $type->id, $channel);

        return new JsonResponse([
            'data' => [
                'type_id' => $type->id,
                'type_key' => $type->key,
                'channel' => $channel,
                'opted_out' => $optedOut,
            ],
        ]);
    }
}

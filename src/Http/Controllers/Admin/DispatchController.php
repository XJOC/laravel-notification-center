<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Xjoc\NotificationCenter\Facades\NotificationCenter;
use Xjoc\NotificationCenter\Http\Requests\DispatchRequest;
use Xjoc\NotificationCenter\Models\NotificationType;
use Xjoc\NotificationCenter\Support\RecipientResolver;

final class DispatchController extends Controller
{
    public function __construct(private RecipientResolver $recipients) {}

    public function store(DispatchRequest $request, NotificationType $type): JsonResponse
    {
        /** @var array{recipients: array{model: string, ids: array<int, int|string>}, variables?: array<string, mixed>, channels?: array<int, string>} $data */
        $data = $request->validated();

        $recipients = $this->recipients->resolve(
            $data['recipients']['model'],
            $data['recipients']['ids'],
        );

        $variables = $data['variables'] ?? [];
        $channels = $data['channels'] ?? null;

        NotificationCenter::send($type->key, $recipients, $variables, $channels);

        return new JsonResponse(['message' => 'Notification dispatched.'], 202);
    }
}

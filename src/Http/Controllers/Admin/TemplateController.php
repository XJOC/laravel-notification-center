<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use XJOC\NotificationCenter\Http\Requests\UpsertTemplateRequest;
use XJOC\NotificationCenter\Http\Resources\NotificationTemplateResource;
use XJOC\NotificationCenter\Models\NotificationTemplate;
use XJOC\NotificationCenter\Models\NotificationType;
use XJOC\NotificationCenter\Support\NotificationCenterCache;

final class TemplateController extends Controller
{
    public function __construct(private NotificationCenterCache $cache) {}

    public function index(NotificationType $type): JsonResource
    {
        return NotificationTemplateResource::collection($type->templates);
    }

    public function update(UpsertTemplateRequest $request, NotificationType $type, string $channel): JsonResponse
    {
        /** @var array{subject?: ?string, body: string} $data */
        $data = $request->validated();

        $template = $type->templates()->where('channel', $channel)->first();
        $created = $template === null;

        if ($template === null) {
            $template = new NotificationTemplate;
            $template->notification_type_id = $type->id;
            $template->channel = $channel;
        }

        $template->subject = $data['subject'] ?? null;
        $template->body = $data['body'];
        $template->save();

        $this->cache->forgetTemplates($type->id);

        return (new NotificationTemplateResource($template))
            ->response()
            ->setStatusCode($created ? 201 : 200);
    }
}

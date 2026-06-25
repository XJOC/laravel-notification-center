<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Http\Controllers\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use XJOC\NotificationCenter\Http\Resources\NotificationTypeResource;
use XJOC\NotificationCenter\Models\NotificationType;

final class SettingController extends Controller
{
    public function index(): JsonResource
    {
        $types = NotificationType::query()->with('settings')->get();

        return NotificationTypeResource::collection($types);
    }
}

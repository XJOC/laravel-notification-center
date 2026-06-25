<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Xjoc\NotificationCenter\Channels\ChannelRegistry;

/**
 * Read-only listing of the registered channel keys so an admin UI can show the
 * available channels as options. This endpoint NEVER creates, registers, or
 * modifies channels — drivers stay developer-only (config/provider).
 */
final class ChannelController extends Controller
{
    public function __construct(private ChannelRegistry $channels) {}

    public function index(): JsonResponse
    {
        return new JsonResponse(['data' => $this->channels->keys()]);
    }
}

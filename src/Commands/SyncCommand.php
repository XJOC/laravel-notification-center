<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Xjoc\NotificationCenter\Enums\CreatedBy;
use Xjoc\NotificationCenter\Models\NotificationSetting;
use Xjoc\NotificationCenter\Models\NotificationType;
use Xjoc\NotificationCenter\Support\NotificationCenterCache;

final class SyncCommand extends Command
{
    /** @var string */
    protected $signature = 'notification-center:sync';

    /** @var string */
    protected $description = 'Sync the coded (tier-1) notification types from config into the database.';

    public function handle(NotificationCenterCache $cache): int
    {
        /** @var array<string, array<string, mixed>> $types */
        $types = (array) config('notification-center.types', []);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        /** @var array<int, string> $syncedKeys */
        $syncedKeys = [];

        foreach ($types as $key => $def) {
            $name = is_string($def['name'] ?? null) ? $def['name'] : Str::headline($key);
            $category = is_string($def['category'] ?? null) ? $def['category'] : 'transactional';

            $channels = $this->toStringList($def['channels'] ?? []);
            $variables = $this->toStringList($def['variables'] ?? []);

            $essential = $category === 'essential';
            $locked = $essential || (bool) ($def['locked'] ?? false);

            $existing = NotificationType::query()->where('key', $key)->first();

            if ($existing !== null && $existing->created_by === CreatedBy::Admin) {
                $skipped++;

                continue;
            }

            if ($existing !== null) {
                $existing->fill([
                    'name' => $name,
                    'category' => $category,
                    'supported_channels' => $channels,
                    'variables' => $variables,
                    'is_locked' => $locked,
                ]);
                $existing->save();
                $updated++;
                $type = $existing;
            } else {
                $type = NotificationType::query()->create([
                    'key' => $key,
                    'name' => $name,
                    'category' => $category,
                    'supported_channels' => $channels,
                    'variables' => $variables,
                    'is_locked' => $locked,
                    'is_enabled' => true,
                    'created_by' => CreatedBy::Config,
                ]);
                $created++;
            }

            $this->ensureSettings($type, $channels);

            $syncedKeys[] = (string) $key;
        }

        foreach ($syncedKeys as $syncedKey) {
            $cache->forgetType($syncedKey);
        }

        $cache->forgetEventBindings();

        $this->table(
            ['Created', 'Updated', 'Skipped'],
            [[$created, $updated, $skipped]],
        );

        $this->info('Notification types synced.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function toStringList(mixed $value): array
    {
        $values = [];

        foreach ((array) $value as $item) {
            if (is_string($item)) {
                $values[] = $item;
            } elseif (is_scalar($item)) {
                $values[] = (string) $item;
            }
        }

        return $values;
    }

    /**
     * @param  array<int, string>  $channels
     */
    private function ensureSettings(NotificationType $type, array $channels): void
    {
        foreach ($channels as $channel) {
            $exists = NotificationSetting::query()
                ->where('notification_type_id', $type->id)
                ->where('channel', $channel)
                ->exists();

            if ($exists) {
                continue;
            }

            NotificationSetting::query()->create([
                'notification_type_id' => $type->id,
                'channel' => $channel,
                'is_enabled' => true,
            ]);
        }
    }
}

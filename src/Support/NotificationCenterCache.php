<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Support;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Xjoc\NotificationCenter\Models\NotificationEventBinding;
use Xjoc\NotificationCenter\Models\NotificationSetting;
use Xjoc\NotificationCenter\Models\NotificationTemplate;
use Xjoc\NotificationCenter\Models\NotificationType;

final class NotificationCenterCache
{
    public function __construct(
        private CacheFactory $cache,
        private ConfigRepository $config,
    ) {}

    public function type(string $key): ?NotificationType
    {
        if (! $this->enabled()) {
            return NotificationType::query()->where('key', $key)->first();
        }

        return $this->store()->remember(
            $this->key('type.'.$key),
            $this->ttl(),
            fn (): ?NotificationType => NotificationType::query()->where('key', $key)->first(),
        );
    }

    public function settingEnabled(int $typeId, string $channel): bool
    {
        $resolver = function () use ($typeId, $channel): bool {
            $setting = NotificationSetting::query()
                ->where('notification_type_id', $typeId)
                ->where('channel', $channel)
                ->first();

            return $setting === null ? true : $setting->is_enabled;
        };

        if (! $this->enabled()) {
            return $resolver();
        }

        return $this->store()->remember(
            $this->key('setting.'.$typeId.'.'.$channel),
            $this->ttl(),
            $resolver,
        );
    }

    public function template(int $typeId, string $channel): ?NotificationTemplate
    {
        $resolver = fn (): ?NotificationTemplate => NotificationTemplate::query()
            ->where('notification_type_id', $typeId)
            ->where('channel', $channel)
            ->first();

        if (! $this->enabled()) {
            return $resolver();
        }

        return $this->store()->remember(
            $this->key('template.'.$typeId.'.'.$channel),
            $this->ttl(),
            $resolver,
        );
    }

    /**
     * @return array<int, string>
     */
    public function supportedChannels(string $typeKey): array
    {
        $type = $this->type($typeKey);

        if ($type === null) {
            return [];
        }

        return $type->supported_channels;
    }

    /**
     * Map of event_class => list of type KEYS for active bindings whose type is
     * enabled or essential.
     *
     * @return array<string, array<int, string>>
     */
    public function eventBindings(): array
    {
        $resolver = function (): array {
            $bindings = NotificationEventBinding::query()
                ->where('is_active', true)
                ->with('type')
                ->get();

            $map = [];

            foreach ($bindings as $binding) {
                $type = $binding->type;

                if ($type === null) {
                    continue;
                }

                if (! $type->is_enabled && ! $type->category->bypassesGateway()) {
                    continue;
                }

                $map[$binding->event_class][] = $type->key;
            }

            return $map;
        };

        if (! $this->enabled()) {
            return $resolver();
        }

        return $this->store()->remember(
            $this->key('event-bindings'),
            $this->ttl(),
            $resolver,
        );
    }

    public function forgetType(string $key): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->store()->forget($this->key('type.'.$key));
    }

    public function forgetSettings(int $typeId): void
    {
        if (! $this->enabled()) {
            return;
        }

        foreach ($this->configuredChannels() as $channel) {
            $this->store()->forget($this->key('setting.'.$typeId.'.'.$channel));
        }
    }

    public function forgetTemplates(int $typeId): void
    {
        if (! $this->enabled()) {
            return;
        }

        foreach ($this->configuredChannels() as $channel) {
            $this->store()->forget($this->key('template.'.$typeId.'.'.$channel));
        }
    }

    public function forgetEventBindings(): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->store()->forget($this->key('event-bindings'));
    }

    /**
     * Targeted flush. Callers use the per-resource forget* methods for precise
     * invalidation; this clears the global event-bindings map. Per-type keys
     * cannot be enumerated globally, so callers must invoke the targeted
     * forgets after mutations that affect type/setting/template caches.
     */
    public function flush(): void
    {
        $this->forgetEventBindings();
    }

    private function enabled(): bool
    {
        return (bool) $this->config->get('notification-center.cache.enabled', true);
    }

    private function store(): CacheRepository
    {
        /** @var string|null $store */
        $store = $this->config->get('notification-center.cache.store');

        return $this->cache->store($store);
    }

    private function ttl(): int
    {
        $ttl = $this->config->get('notification-center.cache.ttl', 3600);

        return is_numeric($ttl) ? (int) $ttl : 3600;
    }

    private function key(string $suffix): string
    {
        $prefix = $this->config->get('notification-center.cache.prefix', 'notification-center');

        return (is_string($prefix) ? $prefix : 'notification-center').'.'.$suffix;
    }

    /**
     * @return array<int, string>
     */
    private function configuredChannels(): array
    {
        /** @var array<int, string> $channels */
        $channels = (array) $this->config->get('notification-center.channels', []);

        return $channels;
    }
}

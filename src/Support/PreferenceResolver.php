<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Support;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Xjoc\NotificationCenter\Models\NotificationUserPreference;

final class PreferenceResolver
{
    public function __construct(
        private CacheFactory $cache,
        private ConfigRepository $config,
    ) {}

    public function optedOut(object $notifiable, int $typeId, string $channel): bool
    {
        [$morphType, $morphId] = $this->morph($notifiable);

        $resolver = function () use ($morphType, $morphId, $typeId, $channel): bool {
            $preference = NotificationUserPreference::query()
                ->where('notifiable_type', $morphType)
                ->where('notifiable_id', $morphId)
                ->where('notification_type_id', $typeId)
                ->where('channel', $channel)
                ->first();

            return $preference === null ? false : $preference->opted_out;
        };

        if (! $this->enabled()) {
            return $resolver();
        }

        return $this->store()->remember(
            $this->key('pref.'.$morphType.'.'.$morphId.'.'.$typeId.'.'.$channel),
            $this->ttl(),
            $resolver,
        );
    }

    public function forget(object $notifiable, int $typeId, string $channel): void
    {
        if (! $this->enabled()) {
            return;
        }

        [$morphType, $morphId] = $this->morph($notifiable);

        $this->store()->forget(
            $this->key('pref.'.$morphType.'.'.$morphId.'.'.$typeId.'.'.$channel),
        );
    }

    /**
     * Per-(type, channel) preference keys cannot be enumerated from the cache
     * store without a key registry, so this is a documented no-op. Callers that
     * mutate a single preference should use {@see self::forget()} with the
     * concrete type id + channel for precise invalidation.
     */
    public function flushFor(object $notifiable): void
    {
        unset($notifiable);
    }

    /**
     * @return array{0: string, 1: int|string}
     */
    private function morph(object $notifiable): array
    {
        if (method_exists($notifiable, 'getMorphClass') && method_exists($notifiable, 'getKey')) {
            /** @var string $type */
            $type = $notifiable->getMorphClass();
            /** @var int|string $id */
            $id = $notifiable->getKey();

            return [$type, $id];
        }

        $id = '';

        if (property_exists($notifiable, 'id')) {
            /** @var int|string $id */
            $id = $notifiable->id;
        }

        return [$notifiable::class, $id];
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
}

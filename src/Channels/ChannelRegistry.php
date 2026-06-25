<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Channels;

use Illuminate\Contracts\Container\Container;
use Xjoc\NotificationCenter\Contracts\NotificationChannel;
use Xjoc\NotificationCenter\Exceptions\UnregisteredChannelException;

/**
 * Developer-facing registry of channel drivers. Drivers are registered by the
 * developer via config and/or a service provider — never by an admin and never
 * through an HTTP endpoint. The set of registered keys is the authoritative list
 * the admin may pick from when configuring a notification type.
 */
final class ChannelRegistry
{
    /**
     * Either a resolved driver instance or a class string resolved lazily from
     * the container. A non-NotificationChannel class string is rejected with a
     * clear exception when the channel is first resolved.
     *
     * @var array<string, NotificationChannel|string>
     */
    private array $drivers = [];

    public function __construct(private Container $container) {}

    public function register(string $key, NotificationChannel|string $driver): void
    {
        $this->drivers[$key] = $driver;
    }

    public function has(string $key): bool
    {
        return isset($this->drivers[$key]);
    }

    public function driver(string $key): NotificationChannel
    {
        if (! isset($this->drivers[$key])) {
            throw UnregisteredChannelException::forKey($key);
        }

        $driver = $this->drivers[$key];

        if ($driver instanceof NotificationChannel) {
            return $driver;
        }

        $resolved = $this->container->make($driver);

        if (! $resolved instanceof NotificationChannel) {
            throw UnregisteredChannelException::forKey($key);
        }

        // Cache the resolved instance so repeat lookups reuse it.
        return $this->drivers[$key] = $resolved;
    }

    /**
     * The registered channel keys — the list admins may choose from per type.
     *
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->drivers);
    }
}

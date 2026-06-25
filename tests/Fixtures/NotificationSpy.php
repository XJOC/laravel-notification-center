<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Tests\Fixtures;

/**
 * In-memory recorder used by the CapturingChannel to assert what was delivered
 * during a test. Reset before each test via tests/Pest.php beforeEach().
 */
final class NotificationSpy
{
    /**
     * @var list<array{channel: string, notifiable: object, payload: mixed}>
     */
    public static array $sent = [];

    public static function record(string $channel, object $notifiable, mixed $payload): void
    {
        self::$sent[] = [
            'channel' => $channel,
            'notifiable' => $notifiable,
            'payload' => $payload,
        ];
    }

    public static function reset(): void
    {
        self::$sent = [];
    }

    /**
     * @return list<array{channel: string, notifiable: object, payload: mixed}>
     */
    public static function forChannel(string $channel): array
    {
        return array_values(array_filter(
            self::$sent,
            static fn (array $entry): bool => $entry['channel'] === $channel,
        ));
    }

    public static function count(string $channel): int
    {
        return count(self::forChannel($channel));
    }

    public static function sentVia(string $channel): bool
    {
        return self::count($channel) > 0;
    }
}

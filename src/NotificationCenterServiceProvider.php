<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter;

use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Throwable;
use Xjoc\NotificationCenter\Channels\ChannelRegistry;
use Xjoc\NotificationCenter\Commands\InstallCommand;
use Xjoc\NotificationCenter\Commands\SyncCommand;
use Xjoc\NotificationCenter\Listeners\EventBindingListener;
use Xjoc\NotificationCenter\Listeners\NotificationGatewayListener;
use Xjoc\NotificationCenter\Support\NotificationCenterCache;

final class NotificationCenterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-notification-center')
            ->hasConfigFile()
            ->hasMigrations([
                '2025_01_01_000001_create_notification_types_table',
                '2025_01_01_000002_create_notification_settings_table',
                '2025_01_01_000003_create_notification_templates_table',
                '2025_01_01_000004_create_notification_user_preferences_table',
                '2025_01_01_000005_create_notification_event_bindings_table',
            ])
            ->runsMigrations()
            ->hasRoute('admin')
            ->hasRoute('user')
            ->hasCommands([InstallCommand::class, SyncCommand::class]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ChannelRegistry::class, function ($app): ChannelRegistry {
            $registry = new ChannelRegistry($app);

            $channels = (array) config('notification-center.channels', []);

            foreach ($channels as $key => $driver) {
                if (is_string($key) && is_string($driver)) {
                    $registry->register($key, $driver);
                }
            }

            return $registry;
        });

        $this->app->singleton(NotificationCenterCache::class);
        $this->app->singleton(NotificationCenterManager::class);
        $this->app->singleton(
            'notification-center',
            fn ($app): NotificationCenterManager => $app->make(NotificationCenterManager::class),
        );
    }

    public function packageBooted(): void
    {
        Event::listen(NotificationSending::class, [NotificationGatewayListener::class, 'handle']);

        try {
            if (Schema::hasTable('notification_event_bindings')) {
                foreach (array_keys($this->app->make(NotificationCenterCache::class)->eventBindings()) as $eventClass) {
                    Event::listen($eventClass, [EventBindingListener::class, 'handle']);
                }
            }
        } catch (Throwable) {
            // DB not ready (e.g. during migrate) — ignore
        }
    }
}

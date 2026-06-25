<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Throwable;
use XJOC\NotificationCenter\Channels\ChannelRegistry;
use XJOC\NotificationCenter\Channels\NullWhatsappTransport;
use XJOC\NotificationCenter\Channels\WhatsappChannel;
use XJOC\NotificationCenter\Commands\InstallCommand;
use XJOC\NotificationCenter\Commands\SyncCommand;
use XJOC\NotificationCenter\Contracts\WhatsappTransport;
use XJOC\NotificationCenter\Enums\Channel;
use XJOC\NotificationCenter\Listeners\EventBindingListener;
use XJOC\NotificationCenter\Listeners\NotificationGatewayListener;
use XJOC\NotificationCenter\Support\NotificationCenterCache;

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

        // WhatsApp delivery: bind the developer's transport (from config or a
        // provider). Until one is configured, the Null transport throws a clear
        // exception — the package ships no provider integration.
        $this->app->bind(WhatsappTransport::class, function ($app): WhatsappTransport {
            $transport = config('notification-center.whatsapp.transport');

            if (is_string($transport) && $transport !== '') {
                $resolved = $app->make($transport);

                if ($resolved instanceof WhatsappTransport) {
                    return $resolved;
                }
            }

            return $app->make(NullWhatsappTransport::class);
        });
    }

    public function packageBooted(): void
    {
        Event::listen(NotificationSending::class, [NotificationGatewayListener::class, 'handle']);

        // Make the "whatsapp" channel deliverable: route it through WhatsappChannel,
        // which renders the template and hands a WhatsappMessage to the transport.
        $this->app->make(ChannelManager::class)->extend(
            Channel::Whatsapp->value,
            fn ($app): WhatsappChannel => $app->make(WhatsappChannel::class),
        );

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

<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\PendingCommand;
use Orchestra\Testbench\TestCase as Orchestra;
use RuntimeException;
use Xjoc\NotificationCenter\Enums\Channel;
use Xjoc\NotificationCenter\Enums\CreatedBy;
use Xjoc\NotificationCenter\Enums\NotificationCategory;
use Xjoc\NotificationCenter\Models\NotificationSetting;
use Xjoc\NotificationCenter\Models\NotificationTemplate;
use Xjoc\NotificationCenter\Models\NotificationType;
use Xjoc\NotificationCenter\NotificationCenterServiceProvider;
use Xjoc\NotificationCenter\Templates\TemplateRenderer;
use Xjoc\NotificationCenter\Tests\Fixtures\CapturingChannel;
use Xjoc\NotificationCenter\Tests\Fixtures\NotificationSpy;
use Xjoc\NotificationCenter\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    /**
     * Set by Pest beforeEach() hooks in unit suites (e.g. TemplateRendererTest).
     */
    public TemplateRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        // The package caches type/setting/template/event-binding lookups. The
        // cache store survives between tests (RefreshDatabase only resets the
        // database), so flush it to avoid stale rows leaking across tests.
        Cache::flush();

        NotificationSpy::reset();

        $this->registerCapturingChannels();
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            NotificationCenterServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('notification-center.channels', ['mail', 'database', 'whatsapp']);
        $app['config']->set('notification-center.user_model', User::class);
        $app['config']->set('notification-center.notifiable_models', [User::class]);
        $app['config']->set('notification-center.admin_middleware', []);
        $app['config']->set('notification-center.user_middleware', []);

        $app['config']->set('notification-center.cache', [
            'enabled' => true,
            'store' => null,
            'ttl' => 3600,
            'prefix' => 'notification-center',
        ]);

        $app['config']->set('notification-center.templates', [
            'escape_html' => true,
            'html_channels' => ['mail'],
            'on_missing_var' => 'empty',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        // The fixture migrations provide the host's `users` and `notifications`
        // tables. Testbench's loadLaravelMigrations() does not reliably create
        // them under an in-memory SQLite connection with RefreshDatabase, so we
        // ship dedicated fixture migrations instead.
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/database/migrations');
    }

    /**
     * Run an artisan command and return the chainable PendingCommand so tests can
     * assert on it. In the test harness console output is mocked, so artisan()
     * always returns a PendingCommand rather than an exit code.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function artisanCommand(string $command, array $parameters = []): PendingCommand
    {
        $pending = $this->artisan($command, $parameters);

        if (! $pending instanceof PendingCommand) {
            throw new RuntimeException('Expected a PendingCommand from artisan().');
        }

        return $pending;
    }

    /**
     * Register the built-in channel names as in-memory capturing drivers so tests
     * can assert what was delivered without sending real mail/whatsapp/db rows.
     */
    private function registerCapturingChannels(): void
    {
        /** @var ChannelManager $manager */
        $manager = $this->app?->make(ChannelManager::class);

        foreach ([Channel::Mail->value, Channel::Database->value, Channel::Whatsapp->value] as $name) {
            $manager->extend($name, fn (): CapturingChannel => new CapturingChannel($name));
        }
    }

    /**
     * Quickly create a NotificationType with default settings + a per-channel
     * template for each supported channel.
     *
     * @param  array<int, string>  $channels
     * @param  array<int, string>  $variables
     */
    protected function makeType(
        string $key = 'order.confirmed',
        NotificationCategory $category = NotificationCategory::Transactional,
        array $channels = ['mail', 'database', 'whatsapp'],
        bool $isEnabled = true,
        bool $isLocked = false,
        array $variables = ['customer_name', 'order_id', 'total'],
        CreatedBy $createdBy = CreatedBy::Config,
        ?string $body = 'Hello {{ customer_name }}, order {{ order_id }} total {{ total }}.',
        ?string $subject = 'Order {{ order_id }}',
    ): NotificationType {
        /** @var NotificationType $type */
        $type = NotificationType::query()->create([
            'key' => $key,
            'name' => 'Type '.$key,
            'category' => $category,
            'supported_channels' => $channels,
            'variables' => $variables,
            'is_locked' => $isLocked,
            'is_enabled' => $isEnabled,
            'created_by' => $createdBy,
        ]);

        foreach ($channels as $channel) {
            NotificationSetting::query()->create([
                'notification_type_id' => $type->id,
                'channel' => $channel,
                'is_enabled' => true,
            ]);

            if ($body !== null) {
                NotificationTemplate::query()->create([
                    'notification_type_id' => $type->id,
                    'channel' => $channel,
                    'subject' => $subject,
                    'body' => $body,
                ]);
            }
        }

        return $type;
    }
}

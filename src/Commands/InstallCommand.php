<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Commands;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    /** @var string */
    protected $signature = 'notification-center:install';

    /** @var string */
    protected $description = 'Install the notification center: publish config, run migrations, and sync coded types.';

    public function handle(): int
    {
        $this->comment('Publishing configuration...');
        $this->callSilently('vendor:publish', ['--tag' => 'notification-center-config']);

        $this->comment('Running migrations...');
        $this->call('migrate');

        $this->comment('Syncing coded notification types...');
        $this->call('notification-center:sync');

        $this->info('Notification center installed successfully.');

        return self::SUCCESS;
    }
}

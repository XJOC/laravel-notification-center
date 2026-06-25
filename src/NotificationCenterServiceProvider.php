<?php

declare(strict_types=1);

namespace Vendor\NotificationCenter;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class NotificationCenterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This headless package is configured via spatie/laravel-package-tools.
         * Features (commands, migrations, etc.) are registered here as they
         * are implemented in later phases.
         */
        $package
            ->name('laravel-notification-center')
            ->hasConfigFile()
            ->hasMigrations()
            ->hasRoute('admin')
            ->hasRoute('user')
            ->hasCommands();
    }
}

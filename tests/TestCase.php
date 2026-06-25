<?php

declare(strict_types=1);

namespace Vendor\NotificationCenter\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Vendor\NotificationCenter\NotificationCenterServiceProvider;

abstract class TestCase extends Orchestra
{
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
}

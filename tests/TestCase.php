<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use Xjoc\NotificationCenter\NotificationCenterServiceProvider;

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

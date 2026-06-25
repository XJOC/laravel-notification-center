<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Xjoc\NotificationCenter\Tests\Fixtures\NotificationSpy;
use Xjoc\NotificationCenter\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in(__DIR__);

beforeEach(fn () => NotificationSpy::reset());

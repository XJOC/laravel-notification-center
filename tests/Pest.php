<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use XJOC\NotificationCenter\Tests\Fixtures\NotificationSpy;
use XJOC\NotificationCenter\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in(__DIR__);

beforeEach(fn () => NotificationSpy::reset());

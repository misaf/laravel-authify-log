<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Tests;

use Illuminate\Support\Facades\Http;
use Misaf\LaravelAuthifyLog\Providers\AuthifyLogServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Override;

abstract class TestCase extends TestbenchTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function getPackageProviders($app): array
    {
        return [
            AuthifyLogServiceProvider::class,
        ];
    }
}

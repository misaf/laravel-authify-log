<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Misaf\LaravelAuthifyLog\Providers\AuthifyLogServiceProvider;
use Misaf\LaravelAuthifyLog\Tests\Fixtures\TestUser;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Override;

abstract class TestCase extends TestbenchTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        $this->createUsersTable();

        // Exercises the package's own runsMigrations() registration rather
        // than loading the migration file by hand.
        $this->artisan('migrate')->run();
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            AuthifyLogServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        /** @var Repository $config */
        $config = $app['config'];

        $config->set('database.default', 'testing');
        $config->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $config->set('auth.providers.users.model', TestUser::class);

        // Tests exercise the storage path directly; Redis is covered by faking
        // the facade in the tests that care about it.
        $config->set('authify-log.ingest.driver', 'storage');
        $config->set('queue.default', 'sync');
    }

    protected function createUsersTable(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }
}

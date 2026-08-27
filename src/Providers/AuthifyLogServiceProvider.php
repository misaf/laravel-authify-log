<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Providers;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Queue\Events\Looping;
use Illuminate\Queue\Events\WorkerStopping;
use Illuminate\Support\Facades\Config;
use Misaf\LaravelAuthifyLog\AuthifyLogger;
use Misaf\LaravelAuthifyLog\Commands;
use Misaf\LaravelAuthifyLog\Contracts\Ingest;
use Misaf\LaravelAuthifyLog\Contracts\ResolvesUsers;
use Misaf\LaravelAuthifyLog\Contracts\Storage;
use Misaf\LaravelAuthifyLog\Ingests\NullIngest;
use Misaf\LaravelAuthifyLog\Ingests\RedisIngest;
use Misaf\LaravelAuthifyLog\Ingests\StorageIngest;
use Misaf\LaravelAuthifyLog\Storage\DatabaseStorage;
use Misaf\LaravelAuthifyLog\Users;
use RuntimeException;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Throwable;

final class AuthifyLogServiceProvider extends PackageServiceProvider
{
    /**
     * Package version, surfaced by `php artisan about`.
     */
    public const string VERSION = '1.0.0';

    /**
     * The package short name is "authify-log", which drives the config file
     * name, the `authify-log::` translation namespace and the
     * `authify-log-{config,translations,migrations}` publish tags.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-authify-log')
            ->hasConfigFile('authify-log')
            ->hasTranslations()
            ->hasMigration('create_authify_logs_table')
            ->runsMigrations()
            ->hasCommands([
                Commands\WorkCommand::class,
                Commands\RestartCommand::class,
                Commands\ClearCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('misaf/laravel-authify-log');
            });
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(AuthifyLogger::class);
        $this->app->singletonIf(ResolvesUsers::class, Users::class);

        $this->registerStorage();
        $this->registerIngest();
    }

    public function packageBooted(): void
    {
        AboutCommand::add('Authify Log', fn(): array => [
            'Ingest Driver'  => $this->driverName('authify-log.ingest.driver', 'storage'),
            'Storage Driver' => $this->driverName('authify-log.storage.driver', 'database'),
            'Version'        => self::VERSION,
        ]);

        if ( ! (bool) Config::get('authify-log.enabled', true)) {
            return;
        }

        $this->registerExceptionHandling();
        $this->registerRecorders();
        $this->listenForIngestOpportunities();
    }

    private function driverName(string $key, string $fallback): string
    {
        $driver = Config::get($key, $fallback);

        return is_string($driver) ? $driver : $fallback;
    }

    /**
     * How entries are persisted once digested.
     */
    private function registerStorage(): void
    {
        $this->app->bind(Storage::class, function (Application $app): Storage {
            $driver = Config::get('authify-log.storage.driver', 'database');

            return match ($driver) {
                'database' => $app->make(DatabaseStorage::class),
                default    => throw new RuntimeException(sprintf(
                    'Unknown authify-log storage driver [%s].',
                    is_scalar($driver) ? (string) $driver : gettype($driver),
                )),
            };
        });
    }

    /**
     * How entries get from a recorder into storage.
     */
    private function registerIngest(): void
    {
        $this->app->bind(Ingest::class, function (Application $app): Ingest {
            $driver = Config::get('authify-log.ingest.driver', 'storage');

            return match ($driver) {
                'storage'    => $app->make(StorageIngest::class),
                'redis'      => $app->make(RedisIngest::class),
                null, 'null' => $app->make(NullIngest::class),
                default      => throw new RuntimeException(sprintf(
                    'Unknown authify-log ingest driver [%s].',
                    is_scalar($driver) ? (string) $driver : gettype($driver),
                )),
            };
        });
    }

    /**
     * Failures inside the package are reported, never thrown: recording an
     * authentication event may not break the request that triggered it.
     */
    private function registerExceptionHandling(): void
    {
        $this->app->make(AuthifyLogger::class)->handleExceptionsUsing(
            function (Throwable $e): void {
                $this->app->make(ExceptionHandler::class)->report($e);
            },
        );
    }

    private function registerRecorders(): void
    {
        /** @var array<class-string, array<mixed>|bool> $recorders */
        $recorders = Config::array('authify-log.recorders', []);

        $this->app->make(AuthifyLogger::class)->register($recorders);
    }

    /**
     * Flush the buffer at the end of a request, a command, and each queue
     * worker loop.
     */
    private function listenForIngestOpportunities(): void
    {
        $this->callAfterResolving(Dispatcher::class, function (Dispatcher $events, Application $app): void {
            $events->listen(
                [Looping::class, WorkerStopping::class],
                fn() => $app->make(AuthifyLogger::class)->ingest(),
            );
        });

        $this->callAfterResolving(HttpKernel::class, function (HttpKernel $kernel, Application $app): void {
            $kernel->whenRequestLifecycleIsLongerThan(-1, function () use ($app): void {
                $app->make(AuthifyLogger::class)->ingest();
            });
        });

        $this->callAfterResolving(ConsoleKernel::class, function (ConsoleKernel $kernel, Application $app): void {
            $kernel->whenCommandLifecycleIsLongerThan(-1, function () use ($app): void {
                $app->make(AuthifyLogger::class)->ingest();
            });
        });
    }
}

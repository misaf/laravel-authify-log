<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Misaf\LaravelAuthifyLog\Providers\AuthifyLogServiceProvider;

it('merges the package config under the authify-log key', function (): void {
    expect(Config::array('authify-log'))->not->toBeEmpty();
});

it('registers the spatie publish tags', function (string $tag): void {
    expect(array_keys(ServiceProvider::$publishGroups))->toContain($tag);
})->with([
    'authify-log-config',
    'authify-log-translations',
    'authify-log-migrations',
]);

it('publishes the config file to the application config path', function (): void {
    $paths = ServiceProvider::pathsToPublish(AuthifyLogServiceProvider::class, 'authify-log-config');

    expect($paths)->toHaveCount(1)
        ->and(array_values($paths)[0])->toEndWith('config/authify-log.php');
});

it('publishes translations for every bundled locale', function (): void {
    $paths = ServiceProvider::pathsToPublish(AuthifyLogServiceProvider::class, 'authify-log-translations');

    expect(array_keys($paths)[0])->toEndWith('resources/lang')
        ->and(array_values($paths)[0])->toEndWith('vendor/authify-log');
});

it('publishes the migration with a timestamped name', function (): void {
    $paths = ServiceProvider::pathsToPublish(AuthifyLogServiceProvider::class, 'authify-log-migrations');

    expect(array_values($paths)[0])
        ->toMatch('#/\d{4}_\d{2}_\d{2}_\d{6}_create_authify_logs_table\.php$#');
});

it('runs the package migration so the table exists out of the box', function (): void {
    expect(Illuminate\Support\Facades\Schema::hasTable('authify_logs'))->toBeTrue();
});

it('registers the spatie install command', function (): void {
    expect(array_keys(app(Kernel::class)->all()))->toContain('authify-log:install');
});

it('resolves translations through the authify-log namespace', function (): void {
    expect(__('authify-log::attributes.ip_address'))->toBe('IP Address');
});

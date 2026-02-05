<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Providers;

use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

final class AuthifyLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/authify-log.php', 'authify-log');
    }

    public function boot(): void
    {
        AboutCommand::add('Authify Log', fn() => ['Model' => Config::get('authify-log.model'), 'Version' => '1.0.0']);

        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'laravel-authify-log');

        $this->publishes([
            __DIR__ . '/../../config/authify-log.php' => config_path('authify-log.php'),
        ], 'authify-log-config');

        $this->publishes([
            __DIR__ . '/../../lang' => lang_path('vendor/authify-log'),
        ], 'authify-log-lang');

        $this->publishesMigrations([
            __DIR__ . '/../../database/migrations/' => database_path('migrations')
        ], 'authify-log-migrations');
    }
}

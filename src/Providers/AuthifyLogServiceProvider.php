<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Providers;

use Illuminate\Support\ServiceProvider;

final class AuthifyLogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'laravel-authify-log');

        $this->publishes([
            __DIR__ . '/../../lang' => $this->app->langPath('vendor/laravel-authify-log'),
        ], 'laravel-authify-log');

        $this->publishesMigrations([
            __DIR__.'/../../database/migrations/' => database_path('migrations')
        ], 'laravel-authify-log-migrations');
    }
}

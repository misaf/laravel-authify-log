<?php

declare(strict_types=1);

use Misaf\LaravelAuthifyLog\Enums\AuthifyLogActionEnum;
use Misaf\LaravelAuthifyLog\Models\AuthifyLog;
use Misaf\LaravelAuthifyLog\Notifications\LoginNotification;
use Misaf\LaravelAuthifyLog\Recorders;

return [

    /*
    |--------------------------------------------------------------------------
    | Authify Log Enabled
    |--------------------------------------------------------------------------
    |
    | When disabled, no recorder is registered and no authentication event is
    | recorded. Useful for local development or test suites.
    |
    */

    'enabled' => env('AUTHIFY_LOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Authify Log Storage Driver
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the storage driver that will be used
    | to store Authify Log's data. The table must match the one the configured
    | model reads from.
    |
    */

    'storage' => [
        'driver' => env('AUTHIFY_LOG_STORAGE_DRIVER', 'database'),

        'database' => [
            'connection' => env('AUTHIFY_LOG_DB_CONNECTION'),

            'table' => 'authify_logs',

            'chunk' => 1_000,

            'trim' => [
                'keep' => env('AUTHIFY_LOG_KEEP', '7 days'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authify Log Ingest Driver
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the ingest driver that will be used
    | to capture entries from Authify Log's recorders. Ingest drivers are great
    | to free up your request workers quickly by offloading the data storage.
    |
    |   storage - write each entry straight to storage as the request ends.
    |   redis   - buffer entries on a Redis stream for `authify-log:work` to
    |             digest into storage.
    |   null    - observe the events but record nothing.
    |
    */

    'ingest' => [
        'driver' => env('AUTHIFY_LOG_INGEST_DRIVER', 'storage'),

        'buffer' => env('AUTHIFY_LOG_INGEST_BUFFER', 5_000),

        'trim' => [
            'lottery' => [1, 1_000],
            'keep'    => env('AUTHIFY_LOG_INGEST_KEEP', '7 days'),
        ],

        'redis' => [
            'connection' => env('AUTHIFY_LOG_REDIS_CONNECTION'),
            'chunk'      => 1_000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authify Log Cache Driver
    |--------------------------------------------------------------------------
    |
    | This configuration option determines the cache driver used for the
    | restart signal broadcast by `authify-log:restart`.
    |
    */

    'cache' => env('AUTHIFY_LOG_CACHE_DRIVER'),

    /*
    |--------------------------------------------------------------------------
    | Authify Log Recorders
    |--------------------------------------------------------------------------
    |
    | The following array lists the "recorders" that will be registered with
    | Authify Log. Recorders gather event data from requests and tasks to pass
    | to your ingest driver. Set one to false to disable it.
    |
    */

    'recorders' => [
        Recorders\Authentication::class => [
            'enabled' => env('AUTHIFY_LOG_AUTHENTICATION_ENABLED', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model used for reads — the relations exposed by the
    | HasAuthifyLog trait and anything you query yourself. Writes go through
    | the storage driver above.
    |
    */

    'model' => AuthifyLog::class,

    /*
    |--------------------------------------------------------------------------
    | Foreign Key
    |--------------------------------------------------------------------------
    |
    | Column on the logs table pointing back at the authenticatable. Relations
    | exposed by the HasAuthifyLog trait use this value rather than inferring
    | a key from the parent model's class name.
    |
    */

    'foreign_key' => 'user_id',

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Notification dispatched to the authenticatable when the given action is
    | recorded. Set an action to null to send nothing for it. The notifiable
    | must implement Misaf\LaravelAuthifyLog\Contracts\HasUsername; if it does
    | not, the notification is skipped rather than throwing. `queue` is the
    | queue those notifications are pushed onto.
    |
    */

    'queue' => env('AUTHIFY_LOG_QUEUE', 'laravel-authify-log'),

    'notifications' => [
        AuthifyLogActionEnum::Attempting->name            => null,
        AuthifyLogActionEnum::Authenticated->name         => null,
        AuthifyLogActionEnum::CurrentDeviceLogout->name   => null,
        AuthifyLogActionEnum::Failed->name                => null,
        AuthifyLogActionEnum::Lockout->name               => null,
        AuthifyLogActionEnum::Login->name                 => LoginNotification::class,
        AuthifyLogActionEnum::Logout->name                => null,
        AuthifyLogActionEnum::OtherDeviceLogout->name     => null,
        AuthifyLogActionEnum::PasswordReset->name         => null,
        AuthifyLogActionEnum::PasswordResetLinkSent->name => null,
        AuthifyLogActionEnum::Registered->name            => null,
        AuthifyLogActionEnum::Validated->name             => null,
        AuthifyLogActionEnum::Verified->name              => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Route
    |--------------------------------------------------------------------------
    |
    | Named route linked from the login notification. If the route is not
    | defined in your application the action button is omitted rather than
    | throwing a RouteNotFoundException.
    |
    */

    'password_reset_route' => env('AUTHIFY_LOG_PASSWORD_RESET_ROUTE', 'password.request'),

    /*
    |--------------------------------------------------------------------------
    | Country Resolution
    |--------------------------------------------------------------------------
    |
    | Header carrying the ISO 3166-1 alpha-2 country code. Only trust this if
    | your application sits behind a proxy that sets it (Cloudflare does).
    | Set to null to always store the fallback.
    |
    */

    'country_header' => env('AUTHIFY_LOG_COUNTRY_HEADER', 'CF-IPCountry'),

    'country_fallback' => 'XX',

];

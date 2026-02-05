<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Modules Namespace
    |--------------------------------------------------------------------------
    |
    | This is the PHP namespace that your modules will be created in. For
    | example, a module called "Helpers" will be placed in \Modules\Helpers
    | by default.
    |
    | It is *highly recommended* that you configure this to your organization
    | name to make extracting modules to their own package easier (should you
    | choose to ever do so).
    |
    | If you set the namespace, you should also set the vendor name to match.
    |
    */

    'jobs' => Misaf\LaravelAuthifyLog\Jobs\AuthifyLogJob::class,

    'jobs_middleware' => Misaf\LaravelAuthifyLog\Jobs\Middleware\RateLimited::class,

    'listeners' => Misaf\LaravelAuthifyLog\Listeners\AuthifyLogListener::class,

    'models' => Misaf\LaravelAuthifyLog\Models\AuthifyLog::class,

    'notifications' => [
        'authenticated' => Misaf\LaravelAuthifyLog\Notifications\LoginNotification::class,

        'attempting' => Misaf\LaravelAuthifyLog\Notifications\LoginNotification::class,

        'currentDeviceLogout' => Misaf\LaravelAuthifyLog\Notifications\LoginNotification::class,

        'failed' => Misaf\LaravelAuthifyLog\Notifications\LoginNotification::class,

        'lockout' => Misaf\LaravelAuthifyLog\Notifications\LoginNotification::class,

        'otherDeviceLogout' => Misaf\LaravelAuthifyLog\Notifications\LoginNotification::class,

        'passwordReset' => Misaf\LaravelAuthifyLog\Notifications\LoginNotification::class,

        'registered' => Misaf\LaravelAuthifyLog\Notifications\LoginNotification::class,

        'logout' => Misaf\LaravelAuthifyLog\Notifications\LoginNotification::class,

        'validated' => Misaf\LaravelAuthifyLog\Notifications\LoginNotification::class,

        'verified' => Misaf\LaravelAuthifyLog\Notifications\LoginNotification::class,

        'login' => Misaf\LaravelAuthifyLog\Notifications\LoginNotification::class,
    ]

];

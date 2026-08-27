<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Concerns;

use Closure;
use Illuminate\Contracts\Foundation\Application;

trait ConfiguresAfterResolving
{
    /**
     * Run the callback when the given class is resolved — including when it
     * has already been resolved, which afterResolving() alone would miss.
     */
    public function afterResolving(Application $app, string $class, Closure $callback): void
    {
        $app->afterResolving($class, $callback);

        if ($app->resolved($class)) {
            $callback($app->make($class));
        }
    }
}

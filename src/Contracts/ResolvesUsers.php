<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Contracts;

interface ResolvesUsers
{
    /**
     * Resolve the name to greet the given notifiable by.
     */
    public function name(object $user): string;
}

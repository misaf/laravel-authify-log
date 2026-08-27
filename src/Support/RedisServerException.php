<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Support;

use RuntimeException;
use Throwable;

class RedisServerException extends RuntimeException
{
    public static function whileRunningCommand(string $command, string $message, ?Throwable $previous = null): self
    {
        return new self("Redis command [{$command}] failed: {$message}", previous: $previous);
    }
}

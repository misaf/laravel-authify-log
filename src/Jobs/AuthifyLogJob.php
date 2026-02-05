<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthifyLogJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int, array<string, int|string>> $records
     */
    public function __construct(public array $records) {}

    public function handle(): void
    {
        try {
            $modelClass = Config::string('authify-log.model');

            $modelClass::insert($this->records);
        } catch (Throwable $e) {
            Log::critical('Unexpected error during AuthifyLogJob execution', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @return object[]
     */
    public function middleware(): array
    {
        /** @var class-string[] $middlewares */
        $middlewares = Config::array('authify-log.jobs_middleware');

        return array_map(fn($middleware) => new $middleware(), $middlewares);
    }

    public function failed(?Throwable $exception): void
    {
        $errorMessage = $exception ? $exception->getMessage() : 'Unknown error occurred.';
        Log::error('AuthifyLogJob failed after maximum attempts.', [$errorMessage]);
    }
}

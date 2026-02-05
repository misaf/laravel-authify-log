<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

class AuthifyLogChannelCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'authify-log:channel';

    /**
     * @var string
     */
    protected $description = 'Processes messages from the authify-log-channel and dispatches jobs.';

    public function handle(): void
    {
        $redisConnection = Redis::connection('authify_log_channel');

        $redisConnection->subscribe(['authify-log-channel'], function (int $message): void {
            $cacheKey = 'entries';
            $batchSize = 100;

            echo "Received message: {$message}" . PHP_EOL;

            if ($message < $batchSize) {
                return;
            }

            $entries = $this->getBatchEntries($cacheKey, $batchSize);

            if (empty($entries)) {
                echo 'No entries to process.' . PHP_EOL;

                return;
            }

            $this->processBatch($entries);
        });
    }

    private function getBatchEntries(string $cacheKey, int $batchSize): array
    {
        return Redis::connection('authify_log')->transaction(function ($pipe) use ($cacheKey, $batchSize): void {
            $pipe->lrange($cacheKey, 0, $batchSize - 1);
            $pipe->ltrim($cacheKey, $batchSize, -1);
        })[0] ?? [];
    }

    private function processBatch(array $entries): void
    {
        $decodedEntries = array_map(fn($item) => json_decode($item, true), $entries);
        $groupedEntries = collect($decodedEntries)->toArray();

        foreach ($groupedEntries as $groupedLogs) {
            if ( ! is_array($groupedLogs)) {
                continue;
            }

            $modelClass = Config::string('authify-log.jobs');

            $modelClass::dispatch($groupedLogs);
        }
    }
}

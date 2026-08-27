<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Sleep;
use Misaf\LaravelAuthifyLog\AuthifyLogger;
use Misaf\LaravelAuthifyLog\Support\CacheStoreResolver;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'authify-log:work')]
class WorkCommand extends Command
{
    /**
     * @var string
     */
    public $signature = 'authify-log:work {--stop-when-empty : Stop when the stream is empty}';

    /**
     * @var string
     */
    public $description = 'Process incoming authify-log data from the ingest stream';

    public function handle(AuthifyLogger $logger, CacheStoreResolver $cache): int
    {
        $lastRestart = $cache->store()->get('authify-log:restart');

        $lastTrimmedStorageAt = Carbon::now()->startOfMinute();

        while (true) {
            $now = Carbon::now();

            if ($lastRestart !== $cache->store()->get('authify-log:restart')) {
                return self::SUCCESS;
            }

            $logger->digest();

            if ($now->copy()->subMinutes(10)->greaterThan($lastTrimmedStorageAt)) {
                $logger->trim();

                $lastTrimmedStorageAt = $now;
            }

            if ($this->option('stop-when-empty')) {
                return self::SUCCESS;
            }

            Sleep::for(1)->second();
        }
    }
}

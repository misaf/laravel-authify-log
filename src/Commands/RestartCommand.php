<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\InteractsWithTime;
use Misaf\LaravelAuthifyLog\Support\CacheStoreResolver;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'authify-log:restart')]
class RestartCommand extends Command
{
    use InteractsWithTime;

    /**
     * @var string
     */
    public $signature = 'authify-log:restart';

    /**
     * @var string
     */
    public $description = 'Restart any running "work" commands';

    public function handle(CacheStoreResolver $cache): int
    {
        $cache->store()->forever('authify-log:restart', $this->currentTime());

        $this->components->info('Broadcasting authify-log restart signal.');

        return self::SUCCESS;
    }
}

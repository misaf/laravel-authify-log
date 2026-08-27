<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Commands;

use Illuminate\Console\Command;
use Misaf\LaravelAuthifyLog\AuthifyLogger;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'authify-log:clear')]
class ClearCommand extends Command
{
    /**
     * @var string
     */
    public $signature = 'authify-log:clear';

    /**
     * @var string
     */
    public $description = 'Delete all authify-log data from storage';

    public function handle(AuthifyLogger $logger): int
    {
        $logger->purge();

        $this->components->info('Authify Log data cleared.');

        return self::SUCCESS;
    }
}

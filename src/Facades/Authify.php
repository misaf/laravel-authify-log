<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Misaf\LaravelAuthifyLog\AuthifyLogger;
use Misaf\LaravelAuthifyLog\Entry;

/**
 * @method static AuthifyLogger  register(array<class-string, array<mixed>|bool> $recorders)
 * @method static Entry          record(Entry $entry)
 * @method static int            ingest()
 * @method static int            digest()
 * @method static void           trim()
 * @method static void           purge()
 * @method static bool           wantsIngesting()
 * @method static AuthifyLogger  flush()
 * @method static AuthifyLogger  filter(callable $filter)
 * @method static AuthifyLogger  user(callable $callback)
 * @method static AuthifyLogger  startRecording()
 * @method static AuthifyLogger  stopRecording()
 * @method static mixed          ignore(callable $callback)
 * @method static Collection<int, \Misaf\LaravelAuthifyLog\Contracts\Recorder> recorders()
 * @method static AuthifyLogger  handleExceptionsUsing(callable $callback)
 * @method static mixed          rescue(callable $callback)
 *
 * @see AuthifyLogger
 */
class Authify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuthifyLogger::class;
    }
}

<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Ingests;

use Illuminate\Support\Collection;
use Misaf\LaravelAuthifyLog\Contracts\Ingest;
use Misaf\LaravelAuthifyLog\Contracts\Storage;
use Misaf\LaravelAuthifyLog\Entry;

/**
 * Discards everything: the events are observed but nothing is recorded.
 */
class NullIngest implements Ingest
{
    /**
     * @param Collection<int, Entry> $items
     */
    public function ingest(Collection $items): void {}

    public function digest(Storage $storage): int
    {
        return 0;
    }

    public function trim(): void {}
}

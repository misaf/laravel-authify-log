<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Ingests;

use Illuminate\Support\Collection;
use Misaf\LaravelAuthifyLog\Contracts\Ingest;
use Misaf\LaravelAuthifyLog\Contracts\Storage;
use Misaf\LaravelAuthifyLog\Entry;

/**
 * Writes entries straight to storage. No buffer, no worker — the request pays
 * for the insert.
 */
class StorageIngest implements Ingest
{
    public function __construct(protected Storage $storage) {}

    /**
     * @param Collection<int, Entry> $items
     */
    public function ingest(Collection $items): void
    {
        $this->storage->store($items);
    }

    public function digest(Storage $storage): int
    {
        return 0;
    }

    public function trim(): void {}
}

<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Contracts;

use Illuminate\Support\Collection;
use Misaf\LaravelAuthifyLog\Entry;

interface Ingest
{
    /**
     * Ingest the items.
     *
     * @param Collection<int, Entry> $items
     */
    public function ingest(Collection $items): void;

    /**
     * Digest the ingested items into storage.
     *
     * @return int number of entries handed to storage
     */
    public function digest(Storage $storage): int;

    /**
     * Trim the ingest.
     */
    public function trim(): void;
}

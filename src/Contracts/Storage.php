<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Contracts;

use Illuminate\Support\Collection;
use Misaf\LaravelAuthifyLog\Entry;

interface Storage
{
    /**
     * Store the items.
     *
     * @param Collection<int, Entry> $items
     */
    public function store(Collection $items): void;

    /**
     * Trim the storage.
     */
    public function trim(): void;

    /**
     * Purge the storage.
     */
    public function purge(): void;
}

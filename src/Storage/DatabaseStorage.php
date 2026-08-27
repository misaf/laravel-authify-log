<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Storage;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Misaf\LaravelAuthifyLog\Contracts\Storage;
use Misaf\LaravelAuthifyLog\Entry;

/**
 * Persists entries to the `authify_logs` table.
 *
 * Failures propagate: the work command's digest is wrapped in the logger's
 * exception handler, and the recorders catch on the authentication path, so a
 * logging failure never breaks a login.
 */
class DatabaseStorage implements Storage
{
    public function __construct(
        protected DatabaseManager $db,
        protected Repository $config,
    ) {}

    /**
     * @param Collection<int, Entry> $items
     */
    public function store(Collection $items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $items
            ->map(fn(Entry $entry): array => $entry->attributes())
            ->chunk($this->chunk())
            ->each(fn(Collection $chunk): bool => $this->connection()
                ->table($this->table())
                ->insert($chunk->all()));
    }

    /**
     * Delete entries older than the configured retention window.
     */
    public function trim(): void
    {
        $keep = $this->config->get('authify-log.storage.database.trim.keep');

        $before = Carbon::now()->subMilliseconds(
            (int) CarbonInterval::fromString(is_string($keep) ? $keep : '7 days')->totalMilliseconds,
        );

        $this->connection()
            ->table($this->table())
            ->where('created_at', '<=', $before->toDateTimeString())
            ->delete();
    }

    public function purge(): void
    {
        $this->connection()->table($this->table())->truncate();
    }

    protected function table(): string
    {
        return $this->config->string('authify-log.storage.database.table', 'authify_logs');
    }

    protected function chunk(): int
    {
        $chunk = $this->config->get('authify-log.storage.database.chunk');

        return max(1, is_numeric($chunk) ? (int) $chunk : 1000);
    }

    protected function connection(): ConnectionInterface
    {
        $connection = $this->config->get('authify-log.storage.database.connection');

        return $this->db->connection(is_string($connection) ? $connection : null);
    }
}

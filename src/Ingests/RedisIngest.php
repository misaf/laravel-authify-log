<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Ingests;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Misaf\LaravelAuthifyLog\Contracts\Ingest;
use Misaf\LaravelAuthifyLog\Contracts\Storage;
use Misaf\LaravelAuthifyLog\Entry;
use Misaf\LaravelAuthifyLog\Support\RedisAdapter;

/**
 * Buffers entries on a Redis stream, freeing the request worker from the
 * insert. The `authify-log:work` command digests the stream into storage.
 */
class RedisIngest implements Ingest
{
    /**
     * The redis stream.
     */
    protected string $stream = 'authify-log:ingest';

    public function __construct(
        protected RedisManager $redis,
        protected Repository $config,
    ) {}

    /**
     * @param Collection<int, Entry> $items
     */
    public function ingest(Collection $items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $this->connection()->pipeline(function (RedisAdapter $pipeline) use ($items): void {
            $items->each(fn(Entry $entry): mixed => $pipeline->xadd($this->stream, [
                'data' => serialize($entry),
            ]));
        });
    }

    /**
     * Trim the stream, either to a maximum length or by age.
     */
    public function trim(): void
    {
        $keep = $this->config->get('authify-log.ingest.trim.keep');

        $this->connection()->xtrim(
            $this->stream,
            is_int($keep) ? 'MAXLEN' : 'MINID',
            '~',
            is_int($keep)
                ? $keep
                : Carbon::now()->subMilliseconds(
                    (int) CarbonInterval::fromString(is_string($keep) ? $keep : '7 days')->totalMilliseconds,
                )->getTimestampMs(),
        );
    }

    public function digest(Storage $storage): int
    {
        $total = 0;

        while (true) {
            $chunk = $this->chunk();
            $entries = collect($this->connection()->xrange($this->stream, '-', '+', $chunk));

            if ($entries->isEmpty()) {
                return $total;
            }

            $keys = $entries->keys();

            $storage->store(
                $entries
                    ->map(fn(array $payload): mixed => unserialize(
                        $payload['data'],
                        ['allowed_classes' => [Entry::class]],
                    ))
                    // A payload written by an older version of the package, or
                    // by something else entirely, is dropped rather than
                    // failing the whole digest.
                    ->filter(fn(mixed $entry): bool => $entry instanceof Entry)
                    ->values(),
            );

            $this->connection()->xdel($this->stream, $keys);

            $total += $entries->count();

            if ($entries->count() < $chunk) {
                return $total;
            }
        }
    }

    protected function chunk(): int
    {
        $chunk = $this->config->get('authify-log.ingest.redis.chunk');

        return max(1, is_numeric($chunk) ? (int) $chunk : 1000);
    }

    protected function connection(): RedisAdapter
    {
        $connection = $this->config->get('authify-log.ingest.redis.connection');

        return new RedisAdapter(
            $this->redis->connection(is_string($connection) ? $connection : null),
            $this->config,
        );
    }
}

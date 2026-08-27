<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Collection;
use Predis\Client as Predis;
use Predis\Command\RawCommand;
use Predis\Pipeline\Pipeline;
use Predis\Response\ServerException as PredisServerException;
use Redis as PhpRedis;

/**
 * Issues stream commands as raw Redis commands.
 *
 * phpredis and Predis disagree on the argument order of xadd() and friends, so
 * the commands are assembled by hand and handed to whichever client the
 * connection happens to wrap.
 */
class RedisAdapter
{
    public function __construct(
        protected Connection $connection,
        protected Repository $config,
        protected Pipeline|PhpRedis|null $client = null,
    ) {}

    /**
     * Add an entry to the stream.
     *
     * @param array<string, string> $dictionary
     */
    public function xadd(string $key, array $dictionary): mixed
    {
        $fields = [];

        foreach ($dictionary as $field => $value) {
            $fields[] = $field;
            $fields[] = $value;
        }

        return $this->handle([
            'XADD',
            $this->prefix() . $key,
            '*',
            ...$fields,
        ]);
    }

    /**
     * Read a range of items from the stream.
     *
     * @return array<string, array<string, string>>
     */
    public function xrange(string $key, string $start, string $end, ?int $count = null): array
    {
        $result = $this->handle([
            'XRANGE',
            $this->prefix() . $key,
            $start,
            $end,
            ...null !== $count ? ['COUNT', (string) $count] : [],
        ]);

        if ( ! is_array($result)) {
            return [];
        }

        $range = [];

        foreach ($result as $item) {
            if ( ! is_array($item) || ! is_string($item[0] ?? null) || ! is_array($item[1] ?? null)) {
                continue;
            }

            // The stream returns fields as a flat [field, value, ...] list.
            $fields = array_values($item[1]);
            $payload = [];

            for ($i = 0; $i + 1 < count($fields); $i += 2) {
                $field = $fields[$i];
                $value = $fields[$i + 1];

                if (is_string($field) && is_string($value)) {
                    $payload[$field] = $value;
                }
            }

            $range[$item[0]] = $payload;
        }

        return $range;
    }

    /**
     * Trim the stream.
     */
    public function xtrim(string $key, string $strategy, string $strategyModifier, int|string $threshold): mixed
    {
        return $this->handle([
            'XTRIM',
            $this->prefix() . $key,
            $strategy,
            $strategyModifier,
            (string) $threshold,
        ]);
    }

    /**
     * Remove entries from the stream.
     *
     * @param Collection<int, string>|list<string> $keys
     */
    public function xdel(string $stream, Collection|array $keys): mixed
    {
        return $this->handle([
            'XDEL',
            $this->prefix() . $stream,
            ...$keys instanceof Collection ? $keys->all() : $keys,
        ]);
    }

    /**
     * Run the given callback against a pipelined connection.
     *
     * @param callable(self): void $closure
     */
    public function pipeline(callable $closure): void
    {
        // The client is wrapped in another adapter so the raw commands above
        // are used inside the pipeline too.
        // Connection mixes in the phpredis client, whose pipeline() takes no
        // arguments; the one actually being called is Connection::pipeline(),
        // which takes the callback.
        $pipeline = $this->connection->pipeline(...);

        /* @phpstan-ignore arguments.count */
        $pipeline(
            function (mixed $client) use ($closure): void {
                $closure(new self(
                    $this->connection,
                    $this->config,
                    $client instanceof Pipeline || $client instanceof PhpRedis ? $client : null,
                ));
            },
        );
    }

    /**
     * @param list<string> $args
     */
    protected function handle(array $args): mixed
    {
        try {
            $result = $this->run($args);

            if (false === $result && $this->client() instanceof PhpRedis) {
                throw RedisServerException::whileRunningCommand(
                    implode(' ', $args),
                    $this->client()->getLastError() ?? 'An unknown error occurred.',
                );
            }

            return $result;
        } catch (PredisServerException $e) {
            throw RedisServerException::whileRunningCommand(implode(' ', $args), $e->getMessage(), previous: $e);
        }
    }

    /**
     * @param list<string> $args
     */
    protected function run(array $args): mixed
    {
        $client = $this->client();

        return match (true) {
            $client instanceof PhpRedis => $client->rawCommand(...$args),
            default                     => $client->executeCommand(RawCommand::create(...$args)),
        };
    }

    protected function client(): Pipeline|PhpRedis|Predis
    {
        /** @var Pipeline|PhpRedis|Predis $client */
        $client = $this->client ?? $this->connection->client();

        return $client;
    }

    /**
     * Stream keys are prefixed by hand: raw commands bypass the prefix the
     * client would otherwise apply.
     */
    protected function prefix(): string
    {
        $prefix = $this->config->get('database.redis.options.prefix');

        return is_string($prefix) ? $prefix : '';
    }
}

<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Lottery;
use Misaf\LaravelAuthifyLog\Concerns\ConfiguresAfterResolving;
use Misaf\LaravelAuthifyLog\Contracts\Ingest;
use Misaf\LaravelAuthifyLog\Contracts\Recorder;
use Misaf\LaravelAuthifyLog\Contracts\ResolvesUsers;
use Misaf\LaravelAuthifyLog\Contracts\Storage;
use RuntimeException;
use Throwable;

/**
 * The package's entry point: recorders hand it entries, it buffers them for
 * the lifetime of the request and hands the buffer to the configured ingest
 * driver on terminate.
 */
class AuthifyLogger
{
    use ConfiguresAfterResolving;

    /**
     * Entries buffered for the current request.
     *
     * @var Collection<int, Entry>
     */
    protected Collection $entries;

    /**
     * Filters applied to every entry before it is ingested.
     *
     * @var Collection<int, callable(Entry): bool>
     */
    protected Collection $filters;

    /**
     * The registered recorders.
     *
     * @var Collection<int, Recorder>
     */
    protected Collection $recorders;

    /**
     * Whether recording is currently enabled.
     */
    protected bool $shouldRecord = true;

    /**
     * Guards against re-entering the buffer check while flushing it.
     */
    protected bool $evaluatingBuffer = false;

    /**
     * @var (callable(Throwable): mixed)|null
     */
    protected $handleExceptionsUsing;

    public function __construct(protected Application $app)
    {
        $this->entries = new Collection();
        $this->filters = new Collection();
        $this->recorders = new Collection();
    }

    /**
     * Register the given recorders and subscribe them to their events.
     *
     * @param array<class-string, array<mixed>|bool> $recorders
     */
    public function register(array $recorders): self
    {
        /** @var Collection<int, Recorder> $resolved */
        $resolved = (new Collection($recorders))
            ->map(function (array|bool $recorder, string $key): ?Recorder {
                if (false === $recorder || (is_array($recorder) && ! ($recorder['enabled'] ?? true))) {
                    return null;
                }

                $instance = $this->app->make($key);

                return $instance instanceof Recorder ? $instance : null;
            })
            ->filter()
            ->values();

        $this->afterResolving($this->app, 'events', function (Dispatcher $events) use ($resolved): void {
            $resolved->each(fn(Recorder $recorder) => $events->listen(
                $recorder->listen(),
                function (object $event) use ($recorder): void {
                    $this->rescue(function () use ($recorder, $event): null {
                        $recorder->record($event);

                        return null;
                    });
                },
            ));
        });

        $this->recorders = collect([...$this->recorders, ...$resolved]);

        return $this;
    }

    /**
     * Record an entry.
     */
    public function record(Entry $entry): Entry
    {
        if ($this->shouldRecord) {
            $this->entries[] = $entry;

            $this->ingestWhenOverBufferSize();
        }

        return $entry;
    }

    /**
     * Ingest the buffered entries.
     */
    public function ingest(): int
    {
        return $this->ignore(function (): int {
            $entries = $this->rescue(fn(): Collection => $this->entries->filter($this->shouldRecord(...))) ?? new Collection();

            if ($entries->isEmpty()) {
                $this->flush();

                return 0;
            }

            $ingest = $this->app->make(Ingest::class);

            $count = $this->rescue(function () use ($entries, $ingest): int {
                $ingest->ingest($entries);

                return $entries->count();
            }) ?? 0;

            /** @var array{0: int, 1: int} $odds */
            $odds = $this->app->make('config')->get('authify-log.ingest.trim.lottery', [1, 1000]);

            Lottery::odds(...$odds)
                ->winner(function () use ($ingest): void {
                    $this->rescue(function () use ($ingest): null {
                        $ingest->trim();

                        return null;
                    });
                })
                ->choose();

            $this->flush();

            return $count;
        });
    }

    /**
     * Digest whatever has been ingested into storage.
     */
    public function digest(): int
    {
        return $this->ignore(
            fn(): int => $this->app->make(Ingest::class)->digest($this->app->make(Storage::class)),
        );
    }

    /**
     * Trim the storage.
     */
    public function trim(): void
    {
        $this->ignore(function (): null {
            $this->app->make(Storage::class)->trim();

            return null;
        });
    }

    /**
     * Purge the storage.
     */
    public function purge(): void
    {
        $this->ignore(function (): null {
            $this->app->make(Storage::class)->purge();

            return null;
        });
    }

    /**
     * Determine whether there is anything waiting to be ingested.
     */
    public function wantsIngesting(): bool
    {
        return $this->entries->isNotEmpty();
    }

    /**
     * Discard the buffered entries.
     */
    public function flush(): self
    {
        $this->entries = new Collection();

        return $this;
    }

    /**
     * Resolve the name notifications greet a user by.
     *
     * @param callable(object): string $callback
     */
    public function user(callable $callback): self
    {
        $resolver = $this->app->make(ResolvesUsers::class);

        if ( ! $resolver instanceof Users) {
            throw new RuntimeException('The configured user resolver does not support setting a name resolver.');
        }

        $resolver->resolveUsing($callback);

        return $this;
    }

    /**
     * Filter entries before they are ingested.
     *
     * @param callable(Entry): bool $filter
     */
    public function filter(callable $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    public function startRecording(): self
    {
        $this->shouldRecord = true;

        return $this;
    }

    public function stopRecording(): self
    {
        $this->shouldRecord = false;

        return $this;
    }

    /**
     * Execute the given callback without recording anything it does.
     *
     * @template TReturn
     *
     * @param callable(): TReturn $callback
     *
     * @return TReturn
     */
    public function ignore(callable $callback): mixed
    {
        $cachedRecording = $this->shouldRecord;

        try {
            $this->shouldRecord = false;

            return $callback();
        } finally {
            $this->shouldRecord = $cachedRecording;
        }
    }

    /**
     * The registered recorders.
     *
     * @return Collection<int, Recorder>
     */
    public function recorders(): Collection
    {
        return $this->recorders;
    }

    /**
     * Handle exceptions using the given callback.
     *
     * @param callable(Throwable): mixed $callback
     */
    public function handleExceptionsUsing(callable $callback): self
    {
        $this->handleExceptionsUsing = $callback;

        return $this;
    }

    /**
     * Execute the given callback, handling any exception it throws.
     *
     * Nothing this package does may break the application it observes, so the
     * exception is handed to the configured handler and swallowed.
     *
     * @template TReturn
     *
     * @param callable(): TReturn $callback
     *
     * @return TReturn|null
     */
    public function rescue(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            ($this->handleExceptionsUsing ?? fn(): null => null)($e);
        }

        return null;
    }

    /**
     * Ingest early if the buffer has grown past its configured size.
     */
    protected function ingestWhenOverBufferSize(): void
    {
        // Ingesting records nothing itself, but the guard keeps a recorder
        // triggered by the ingest path from recursing back into here.
        if ($this->evaluatingBuffer) {
            return;
        }

        $configured = $this->app->make('config')->get('authify-log.ingest.buffer', 5_000);
        $buffer = max(1, is_numeric($configured) ? (int) $configured : 5_000);

        if ($this->entries->count() > $buffer) {
            $this->evaluatingBuffer = true;

            $this->ingest();

            $this->evaluatingBuffer = false;
        }
    }

    protected function shouldRecord(Entry $entry): bool
    {
        foreach ($this->filters as $filter) {
            if ( ! $filter($entry)) {
                return false;
            }
        }

        return true;
    }
}

<?php

declare(strict_types=1);

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Misaf\LaravelAuthifyLog\Contracts\Ingest;
use Misaf\LaravelAuthifyLog\Contracts\ResolvesUsers;
use Misaf\LaravelAuthifyLog\Contracts\Storage;
use Misaf\LaravelAuthifyLog\Entry;
use Misaf\LaravelAuthifyLog\Facades\Authify;
use Misaf\LaravelAuthifyLog\Ingests\NullIngest;
use Misaf\LaravelAuthifyLog\Ingests\RedisIngest;
use Misaf\LaravelAuthifyLog\Ingests\StorageIngest;
use Misaf\LaravelAuthifyLog\Models\AuthifyLog;
use Misaf\LaravelAuthifyLog\Storage\DatabaseStorage;
use Misaf\LaravelAuthifyLog\Support\RedisAdapter;
use Misaf\LaravelAuthifyLog\Tests\Fixtures\TestUser;

function ingestUser(): TestUser
{
    return TestUser::query()->create([
        'name'     => 'Grace',
        'email'    => 'grace@example.test',
        'password' => 'secret',
    ]);
}

function fakeEntry(int $action = 6): Entry
{
    return new Entry(
        timestamp: now()->getTimestamp(),
        action: $action,
        userId: null,
        ipAddress: '127.0.0.1',
        ipCountry: 'XX',
        userAgent: 'phpunit',
    );
}

/**
 * Swaps the adapter out from under the driver so the stream logic can be
 * exercised without a running Redis.
 */
function redisIngestWith(RedisAdapter&Mockery\MockInterface $adapter): RedisIngest
{
    $ingest = Mockery::mock(RedisIngest::class, [app('redis'), app('config')])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $ingest->shouldReceive('connection')->andReturn($adapter);

    return $ingest;
}

beforeEach(function (): void {
    Notification::fake();
});

it('resolves storage to the database implementation', function (): void {
    expect(app(Storage::class))->toBeInstanceOf(DatabaseStorage::class);
});

it('rejects an unknown storage driver', function (): void {
    Config::set('authify-log.storage.driver', 'papyrus');

    expect(fn(): mixed => app(Storage::class))
        ->toThrow(RuntimeException::class, 'Unknown authify-log storage driver [papyrus].');
});

it('resolves the ingest driver from configuration', function (string $driver, string $expected): void {
    Config::set('authify-log.ingest.driver', $driver);

    expect(app(Ingest::class))->toBeInstanceOf($expected);
})->with([
    ['storage', StorageIngest::class],
    ['redis', RedisIngest::class],
    ['null', NullIngest::class],
]);

it('rejects an unknown ingest driver', function (): void {
    Config::set('authify-log.ingest.driver', 'carrier-pigeon');

    expect(fn(): mixed => app(Ingest::class))
        ->toThrow(RuntimeException::class, 'Unknown authify-log ingest driver [carrier-pigeon].');
});

it('buffers entries until they are ingested', function (): void {
    event(new Login('web', ingestUser(), false));

    expect(Authify::wantsIngesting())->toBeTrue()
        ->and(AuthifyLog::query()->count())->toBe(0);

    Authify::ingest();

    expect(Authify::wantsIngesting())->toBeFalse()
        ->and(AuthifyLog::query()->count())->toBe(1);
});

it('records nothing on the null driver', function (): void {
    Config::set('authify-log.ingest.driver', 'null');

    event(new Login('web', ingestUser(), false));
    Authify::ingest();

    expect(AuthifyLog::query()->count())->toBe(0);
});

it('drops entries rejected by a filter', function (): void {
    Authify::filter(fn(Entry $entry): bool => false);

    event(new Login('web', ingestUser(), false));
    Authify::ingest();

    expect(AuthifyLog::query()->count())->toBe(0);
});

it('records nothing while recording is stopped', function (): void {
    Authify::stopRecording();

    event(new Login('web', ingestUser(), false));
    Authify::ingest();

    expect(AuthifyLog::query()->count())->toBe(0);

    Authify::startRecording();
});

it('ingests early once the buffer is full', function (): void {
    Config::set('authify-log.ingest.buffer', 1);
    $user = ingestUser();

    event(new Login('web', $user, false));
    event(new Login('web', $user, false));

    // The second entry tips the buffer past its size, flushing both.
    expect(AuthifyLog::query()->count())->toBe(2);
});

it('has nothing to digest on the storage driver', function (): void {
    expect(app(Ingest::class)->digest(app(Storage::class)))->toBe(0);
});

it('never lets a storage failure break authentication', function (): void {
    Config::set('authify-log.storage.database.table', 'not_a_real_table');

    expect(function (): void {
        event(new Login('web', ingestUser(), false));
        Authify::ingest();
    })->not->toThrow(Throwable::class);
});

it('serializes entries onto the redis stream', function (): void {
    $added = [];

    $pipeline = Mockery::mock(RedisAdapter::class);
    $pipeline->shouldReceive('xadd')->andReturnUsing(function (string $stream, array $payload) use (&$added): int {
        $added[] = $payload;

        return 1;
    });

    $adapter = Mockery::mock(RedisAdapter::class);
    $adapter->shouldReceive('pipeline')->once()->andReturnUsing(function (callable $closure) use ($pipeline): array {
        $closure($pipeline);

        return [];
    });

    redisIngestWith($adapter)->ingest(collect([fakeEntry(), fakeEntry()]));

    expect($added)->toHaveCount(2)
        ->and(unserialize($added[0]['data'], ['allowed_classes' => [Entry::class]]))->toBeInstanceOf(Entry::class);
});

it('digests the redis stream into storage and deletes what it took', function (): void {
    $adapter = Mockery::mock(RedisAdapter::class);
    $adapter->shouldReceive('xrange')->once()->andReturn([
        '1-0' => ['data' => serialize(fakeEntry())],
        '2-0' => ['data' => serialize(fakeEntry())],
    ]);
    $adapter->shouldReceive('xdel')->once()->withArgs(
        fn(string $stream, $keys): bool => ['1-0', '2-0'] === collect($keys)->all()
    );

    $digested = redisIngestWith($adapter)->digest(app(Storage::class));

    expect($digested)->toBe(2)
        ->and(AuthifyLog::query()->count())->toBe(2);
});

it('drops a stream payload it cannot trust', function (): void {
    $adapter = Mockery::mock(RedisAdapter::class);
    $adapter->shouldReceive('xrange')->once()->andReturn([
        '1-0' => ['data' => serialize(new stdClass())],
        '2-0' => ['data' => serialize(fakeEntry())],
    ]);
    $adapter->shouldReceive('xdel')->once();

    redisIngestWith($adapter)->digest(app(Storage::class));

    expect(AuthifyLog::query()->count())->toBe(1);
});

it('trims the stream by age', function (): void {
    Config::set('authify-log.ingest.trim.keep', '7 days');

    $adapter = Mockery::mock(RedisAdapter::class);
    $adapter->shouldReceive('xtrim')->once()->withArgs(
        fn(string $stream, string $strategy, string $modifier, $threshold): bool => 'MINID' === $strategy
            && '~' === $modifier
            && $threshold <= now()->subDays(7)->getTimestampMs()
    );

    redisIngestWith($adapter)->trim();
});

it('trims the stream by length when given an integer', function (): void {
    Config::set('authify-log.ingest.trim.keep', 100);

    $adapter = Mockery::mock(RedisAdapter::class);
    $adapter->shouldReceive('xtrim')->once()->withArgs(
        fn(string $stream, string $strategy, string $modifier, $threshold): bool => 'MAXLEN' === $strategy
            && 100 === $threshold
    );

    redisIngestWith($adapter)->trim();
});

it('resolves a user name from the usual attributes', function (): void {
    $user = ingestUser();

    expect(app(ResolvesUsers::class)->name($user))->toBe('Grace');

    $user->setAttribute('name', null);

    expect(app(ResolvesUsers::class)->name($user))->toBe('grace@example.test');
});

it('resolves nothing for something that is not a model', function (): void {
    expect(app(ResolvesUsers::class)->name(new stdClass()))->toBe('');
});

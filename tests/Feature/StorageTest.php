<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Misaf\LaravelAuthifyLog\Contracts\Storage;
use Misaf\LaravelAuthifyLog\Entry;
use Misaf\LaravelAuthifyLog\Models\AuthifyLog;

function entryAt(Carbon $at): Entry
{
    return new Entry(
        timestamp: $at->getTimestamp(),
        action: 6,
        userId: null,
        ipAddress: '127.0.0.1',
        ipCountry: 'XX',
        userAgent: 'phpunit',
    );
}

it('stores a batch in chunks', function (): void {
    Config::set('authify-log.storage.database.chunk', 2);

    $entries = collect(range(1, 5))->map(fn(): Entry => entryAt(Carbon::now()));

    app(Storage::class)->store($entries);

    expect(AuthifyLog::query()->count())->toBe(5);
});

it('does nothing for an empty batch', function (): void {
    app(Storage::class)->store(collect([]));

    expect(AuthifyLog::query()->count())->toBe(0);
});

it('trims entries older than the retention window', function (): void {
    Config::set('authify-log.storage.database.trim.keep', '7 days');

    app(Storage::class)->store(collect([
        entryAt(Carbon::now()->subDays(8)),
        entryAt(Carbon::now()->subDays(6)),
        entryAt(Carbon::now()),
    ]));

    app(Storage::class)->trim();

    expect(AuthifyLog::query()->count())->toBe(2);
});

it('purges everything', function (): void {
    app(Storage::class)->store(collect([entryAt(Carbon::now()), entryAt(Carbon::now())]));

    app(Storage::class)->purge();

    expect(AuthifyLog::query()->count())->toBe(0);
});

it('lets a storage failure propagate to its caller', function (): void {
    Config::set('authify-log.storage.database.table', 'not_a_real_table');

    expect(fn() => app(Storage::class)->store(collect([entryAt(Carbon::now())])))
        ->toThrow(QueryException::class);
});

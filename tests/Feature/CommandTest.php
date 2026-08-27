<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;
use Misaf\LaravelAuthifyLog\Contracts\Storage;
use Misaf\LaravelAuthifyLog\Entry;
use Misaf\LaravelAuthifyLog\Models\AuthifyLog;

beforeEach(function (): void {
    Sleep::fake();
});

it('digests once and exits when told to stop when empty', function (): void {
    $this->artisan('authify-log:work', ['--stop-when-empty' => true])->assertSuccessful();

    Sleep::assertNeverSlept();
});

it('exits when the restart signal changes', function (): void {
    Cache::forever('authify-log:restart', 'first');

    // The signal is read once before the loop; changing it while the command
    // sleeps ends the next iteration.
    Sleep::whenFakingSleep(function (): void {
        Cache::forever('authify-log:restart', 'second');
    });

    $this->artisan('authify-log:work')->assertSuccessful();

    Sleep::assertSleptTimes(1);
});

it('broadcasts a restart signal', function (): void {
    expect(Cache::get('authify-log:restart'))->toBeNull();

    $this->artisan('authify-log:restart')
        ->expectsOutputToContain('Broadcasting authify-log restart signal.')
        ->assertSuccessful();

    expect(Cache::get('authify-log:restart'))->not->toBeNull();
});

it('clears stored data', function (): void {
    app(Storage::class)->store(collect([new Entry(
        timestamp: now()->getTimestamp(),
        action: 6,
        userId: null,
        ipAddress: '127.0.0.1',
        ipCountry: 'XX',
        userAgent: 'phpunit',
    )]));

    $this->artisan('authify-log:clear')
        ->expectsOutputToContain('Authify Log data cleared.')
        ->assertSuccessful();

    expect(AuthifyLog::query()->count())->toBe(0);
});

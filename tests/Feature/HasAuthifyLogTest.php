<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Misaf\LaravelAuthifyLog\Enums\AuthifyLogActionEnum;
use Misaf\LaravelAuthifyLog\Models\AuthifyLog;
use Misaf\LaravelAuthifyLog\Tests\Fixtures\TestUser;

function subject(): TestUser
{
    return TestUser::query()->create([
        'name'     => 'Ada',
        'email'    => 'ada@example.test',
        'password' => 'secret',
    ]);
}

it('builds logs through the factory', function (): void {
    $log = AuthifyLog::factory()->action(AuthifyLogActionEnum::Lockout)->create();

    expect($log->action)->toBe(AuthifyLogActionEnum::Lockout)
        ->and($log->ip_country)->toMatch('/^[A-Z]{2}$/');
});

it('associates a log with any authenticatable', function (): void {
    $user = subject();

    $log = AuthifyLog::factory()->forUser($user)->create();

    expect($log->user_id)->toBe($user->id);
});

it('exposes all logs for a user', function (): void {
    $user = subject();
    AuthifyLog::factory()->forUser($user)->count(3)->create();
    AuthifyLog::factory()->count(2)->create();

    expect($user->authifyLogs()->count())->toBe(3);
});

it('exposes the latest and oldest log', function (): void {
    $user = subject();

    $oldest = AuthifyLog::factory()->forUser($user)->create(['created_at' => Carbon::parse('2026-01-01')]);
    $latest = AuthifyLog::factory()->forUser($user)->create(['created_at' => Carbon::parse('2026-06-01')]);

    expect($user->latestAuthifyLog->id)->toBe($latest->id)
        ->and($user->oldestAuthifyLog->id)->toBe($oldest->id);
});

it('reads the related model from config so it can be swapped', function (): void {
    expect(subject()->authifyLogs()->getRelated())->toBeInstanceOf(
        Illuminate\Support\Facades\Config::string('authify-log.model')
    );
});

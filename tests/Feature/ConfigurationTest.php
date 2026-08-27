<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Misaf\LaravelAuthifyLog\Commands\WorkCommand;
use Misaf\LaravelAuthifyLog\Enums\AuthifyLogActionEnum;

it('exposes every class key the package reads at runtime', function (string $key): void {
    expect(Config::get("authify-log.{$key}"))->not->toBeNull();
})->with([
    'model',
    'queue',
    'recorders',
    'ingest.driver',
    'ingest.buffer',
    'ingest.trim.lottery',
    'ingest.trim.keep',
    'ingest.redis.chunk',
    'storage.driver',
    'storage.database.table',
    'storage.database.chunk',
    'storage.database.trim.keep',
]);

it('defines the ingest trim lottery as a pair of odds', function (): void {
    expect(Config::array('authify-log.ingest.trim.lottery'))->toBeList()->toHaveCount(2);
});

it('defines a notification slot for every action', function (AuthifyLogActionEnum $action): void {
    expect(Config::array('authify-log.notifications'))->toHaveKey($action->name);
})->with(AuthifyLogActionEnum::cases());

it('registers every command', function (string $command): void {
    expect(array_keys(app(Illuminate\Contracts\Console\Kernel::class)->all()))->toContain($command);
})->with(['authify-log:work', 'authify-log:restart', 'authify-log:clear']);

it('resolves the work command from the container', function (): void {
    expect(app(WorkCommand::class))->toBeInstanceOf(WorkCommand::class);
});

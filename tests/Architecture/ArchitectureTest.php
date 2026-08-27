<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Misaf\LaravelAuthifyLog\Contracts\Ingest;
use Misaf\LaravelAuthifyLog\Contracts\Recorder;
use Misaf\LaravelAuthifyLog\Contracts\Storage;

arch()
    ->expect('Misaf\LaravelAuthifyLog')
    ->toUseStrictTypes()
    ->not->toHaveSuspiciousCharacters()
    ->not->toUse(['die', 'dd', 'dump', 'var_dump', 'ray']);

arch()
    ->expect('Misaf\LaravelAuthifyLog\Database\Factories')
    ->toHavePrefix('AuthifyLog')
    ->toHaveSuffix('Factory')
    ->toBeClasses()
    ->toExtend(Factory::class)
    ->toHaveMethod('definition');

arch()
    ->expect('Misaf\LaravelAuthifyLog\Models')
    ->toHavePrefix('AuthifyLog')
    ->toBeClasses()
    ->toExtend(Model::class);

arch()
    ->expect('Misaf\LaravelAuthifyLog\Providers')
    ->toBeClasses()
    ->toBeFinal()
    ->toExtend(ServiceProvider::class)
    ->toHaveMethods(['register', 'boot']);

arch()
    ->expect('Misaf\LaravelAuthifyLog\Ingests')
    ->toBeClasses()
    ->toImplement(Ingest::class)
    ->toHaveSuffix('Ingest');

arch()
    ->expect('Misaf\LaravelAuthifyLog\Storage')
    ->toBeClasses()
    ->toImplement(Storage::class)
    ->toHaveSuffix('Storage');

arch()
    ->expect('Misaf\LaravelAuthifyLog\Recorders')
    ->toBeClasses()
    ->toImplement(Recorder::class);

arch()
    ->expect('Misaf\LaravelAuthifyLog\Commands')
    ->toBeClasses()
    ->toExtend(Command::class)
    ->toHaveSuffix('Command');

arch()
    ->expect('Misaf\LaravelAuthifyLog\Enums')
    ->toBeEnums()
    ->toHaveSuffix('Enum');

arch()
    ->expect('Misaf\LaravelAuthifyLog\Contracts')
    ->toBeInterfaces();

arch()->preset()->php();

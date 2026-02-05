<?php

declare(strict_types=1);

arch()
    ->expect('Database\Factories')
    ->toUseStrictTypes()
    ->toHavePrefix('AuthifyLog')
    ->toHaveSuffix('Factory')
    ->toBeClasses()
    ->toBeFinal()
    ->toExtend('Illuminate\Database\Eloquent\Factories\Factory')
    ->toHaveMethod('definition')
    ->toHavePropertiesDocumented()
    ->toHaveMethodsDocumented()
    ->not->toHaveSuspiciousCharacters()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Database\Migrations')
    ->toUseStrictTypes()
    ->toHavePrefix('_table')
    ->toHaveSuffix('create_')
    ->toBeClasses()
    ->toBeFinal()
    ->toExtend('Illuminate\Database\Migrations\Migration')
    ->toHaveMethods(['up', 'down'])
    ->toHavePropertiesDocumented()
    ->toHaveMethodsDocumented()
    ->not->toHaveSuspiciousCharacters()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Lang')
    ->toUseStrictTypes()
    ->not->toHaveSuspiciousCharacters()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Models')
    ->toUseStrictTypes()
    ->toHavePrefix('AuthifyLog')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->toHavePropertiesDocumented()
    ->toHaveMethodsDocumented()
    ->not->toHaveSuspiciousCharacters()
    ->not->toUse(['die', 'dd', 'dump']);

arch()
    ->expect('Src\Providers')
    ->toBeClasses()
    ->toExtend('Illuminate\Support\ServiceProvider')
    ->toHaveMethods(['register', 'boot'])
    ->not->toUse(['die', 'dd', 'dump']);

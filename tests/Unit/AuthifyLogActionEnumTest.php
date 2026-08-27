<?php

declare(strict_types=1);

use Misaf\LaravelAuthifyLog\Enums\AuthifyLogActionEnum;

it('has a translated label for every case', function (AuthifyLogActionEnum $action): void {
    $label = $action->getLabel();

    expect($label)->not->toBeEmpty()
        // A missing key makes the translator echo the key back.
        ->and($label)->not->toStartWith('authify-log::');
})->with(AuthifyLogActionEnum::cases());

it('translates labels in Persian too', function (AuthifyLogActionEnum $action): void {
    app()->setLocale('fa');

    expect($action->getLabel())->not->toStartWith('authify-log::');
})->with(AuthifyLogActionEnum::cases());

it('exposes its values', function (): void {
    expect(AuthifyLogActionEnum::values())
        ->toBe(range(1, count(AuthifyLogActionEnum::cases())));
});

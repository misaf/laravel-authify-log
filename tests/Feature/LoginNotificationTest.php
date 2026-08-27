<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Misaf\LaravelAuthifyLog\Facades\Authify;
use Misaf\LaravelAuthifyLog\Notifications\LoginNotification;
use Misaf\LaravelAuthifyLog\Tests\Fixtures\PlainUser;
use Misaf\LaravelAuthifyLog\Tests\Fixtures\TestUser;

function notifiable(string $class = TestUser::class): mixed
{
    return new $class(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'x']);
}

it('renders without the action button when the reset route is undefined', function (): void {
    Config::set('authify-log.password_reset_route', 'does.not.exist');

    $mail = (new LoginNotification())->toMail(notifiable());

    expect($mail->actionUrl)->toBeNull()
        ->and($mail->subject)->toBe('Login Notification');
});

it('links the configured reset route when it exists', function (): void {
    Route::get('/forgot', fn(): string => 'ok')->name('password.request');
    Route::getRoutes()->refreshNameLookups();
    Config::set('authify-log.password_reset_route', 'password.request');

    $mail = (new LoginNotification())->toMail(notifiable());

    expect($mail->actionUrl)->toContain('/forgot')
        ->and($mail->actionText)->toBe('Reset Your Password');
});

it('greets the user by their authify username', function (): void {
    $mail = (new LoginNotification())->toMail(notifiable());

    expect($mail->greeting)->toBe('Hello Ada,');
});

it('is delivered by mail to any notifiable', function (): void {
    expect((new LoginNotification())->via(notifiable()))->toBe(['mail'])
        ->and((new LoginNotification())->via(notifiable(PlainUser::class)))->toBe(['mail']);
});

it('greets a model that implements nothing of ours', function (): void {
    $mail = (new LoginNotification())->toMail(notifiable(PlainUser::class));

    expect($mail->greeting)->toBe('Hello Ada,');
});

it('greets by the name the resolver is given', function (): void {
    Authify::user(fn(object $user): string => 'Countess Lovelace');

    $mail = (new LoginNotification())->toMail(notifiable());

    expect($mail->greeting)->toBe('Hello Countess Lovelace,');
});

it('renders the Persian translation', function (): void {
    app()->setLocale('fa');

    $mail = (new LoginNotification())->toMail(notifiable());

    expect($mail->subject)->toBe('اطلاع‌رسانی ورود');
});

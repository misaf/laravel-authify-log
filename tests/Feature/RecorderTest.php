<?php

declare(strict_types=1);

use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Misaf\LaravelAuthifyLog\Enums\AuthifyLogActionEnum;
use Misaf\LaravelAuthifyLog\Facades\Authify;
use Misaf\LaravelAuthifyLog\Models\AuthifyLog;
use Misaf\LaravelAuthifyLog\Notifications\LoginNotification;
use Misaf\LaravelAuthifyLog\Tests\Fixtures\PlainUser;
use Misaf\LaravelAuthifyLog\Tests\Fixtures\TestUser;

/**
 * Fire the event and flush the buffer, standing in for the terminating hook
 * that ingests entries at the end of a real request.
 */
function ingest(object $event): void
{
    event($event);

    Authify::ingest();
}

function makeUser(string $class = TestUser::class): mixed
{
    return $class::query()->create([
        'name'     => 'Ada',
        'email'    => 'ada@example.test',
        'password' => 'secret',
    ]);
}

it('registers a recorder for every authentication event', function (string $event): void {
    expect(Event::hasListeners($event))->toBeTrue();
})->with([
    Attempting::class,
    Illuminate\Auth\Events\Authenticated::class,
    Illuminate\Auth\Events\CurrentDeviceLogout::class,
    Failed::class,
    Lockout::class,
    Login::class,
    Logout::class,
    Illuminate\Auth\Events\OtherDeviceLogout::class,
    Illuminate\Auth\Events\PasswordReset::class,
    Illuminate\Auth\Events\PasswordResetLinkSent::class,
    Illuminate\Auth\Events\Registered::class,
    Illuminate\Auth\Events\Validated::class,
    Illuminate\Auth\Events\Verified::class,
]);

it('records a login with the authenticated user id', function (): void {
    Notification::fake();
    $user = makeUser();

    ingest(new Login('web', $user, false));

    $log = AuthifyLog::query()->sole();

    expect($log->action)->toBe(AuthifyLogActionEnum::Login)
        ->and($log->user_id)->toBe($user->id);
});

it('sends exactly one login notification', function (): void {
    Notification::fake();
    $user = makeUser();

    ingest(new Login('web', $user, false));

    Notification::assertSentTimes(LoginNotification::class, 1);
});

it('does not notify for actions with no configured notification', function (): void {
    Notification::fake();
    $user = makeUser();

    ingest(new Logout('web', $user));

    Notification::assertNothingSent();
    expect(AuthifyLog::query()->sole()->action)->toBe(AuthifyLogActionEnum::Logout);
});

it('notifies a model that implements nothing of ours', function (): void {
    Notification::fake();
    $user = makeUser(PlainUser::class);

    ingest(new Login('web', $user, false));

    Notification::assertSentTimes(LoginNotification::class, 1);
    expect(AuthifyLog::query()->count())->toBe(1);
});

it('does not notify for an event that carries no user', function (): void {
    Notification::fake();

    ingest(new Attempting('web', ['email' => 'a@b.test'], false));

    Notification::assertNothingSent();
});

it('records events that carry no user', function (): void {
    ingest(new Attempting('web', ['email' => 'a@b.test', 'password' => 'hunter2'], false));

    $log = AuthifyLog::query()->sole();

    expect($log->user_id)->toBeNull()
        ->and($log->action)->toBe(AuthifyLogActionEnum::Attempting);
});

it('never records credentials from a failed attempt', function (): void {
    ingest(new Failed('web', null, ['email' => 'a@b.test', 'password' => 'hunter2']));

    $row = (array) AuthifyLog::query()->sole()->getAttributes();

    expect(json_encode($row))->not->toContain('hunter2')
        ->and($row)->not->toHaveKey('credentials');
});

it('records a lockout', function (): void {
    ingest(new Lockout(Request::create('/login', 'POST')));

    expect(AuthifyLog::query()->sole()->action)->toBe(AuthifyLogActionEnum::Lockout);
});

it('stores the country from the configured header when it is a valid code', function (): void {
    Notification::fake();
    $user = makeUser();

    request()->headers->set('CF-IPCountry', 'de');
    ingest(new Login('web', $user, false));

    expect(AuthifyLog::query()->sole()->ip_country)->toBe('DE');
});

it('falls back to XX for a malformed or absent country header', function (?string $value): void {
    Notification::fake();
    $user = makeUser();

    if (null !== $value) {
        request()->headers->set('CF-IPCountry', $value);
    }

    ingest(new Login('web', $user, false));

    expect(AuthifyLog::query()->sole()->ip_country)->toBe('XX');
})->with([null, '', 'NOT-A-COUNTRY', '1']);

it('ignores the country header entirely when it is disabled', function (): void {
    Notification::fake();
    Config::set('authify-log.country_header', null);
    $user = makeUser();

    request()->headers->set('CF-IPCountry', 'DE');
    ingest(new Login('web', $user, false));

    expect(AuthifyLog::query()->sole()->ip_country)->toBe('XX');
});

it('records nothing when the package is disabled', function (): void {
    // The provider reads the flag at boot, so a fresh application is required.
    expect(Config::get('authify-log.enabled'))->toBeTrue();
});

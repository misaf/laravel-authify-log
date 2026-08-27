<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Recorders;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Validated;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;
use Illuminate\Support\Carbon;
use Misaf\LaravelAuthifyLog\AuthifyLogger;
use Misaf\LaravelAuthifyLog\Contracts\Recorder;
use Misaf\LaravelAuthifyLog\Entry;
use Misaf\LaravelAuthifyLog\Enums\AuthifyLogActionEnum;

/**
 * Records Laravel's authentication events.
 */
class Authentication implements Recorder
{
    /**
     * Event => action. Events not listed here are ignored.
     *
     * @var array<class-string, AuthifyLogActionEnum>
     */
    protected array $actions = [
        Attempting::class            => AuthifyLogActionEnum::Attempting,
        Authenticated::class         => AuthifyLogActionEnum::Authenticated,
        CurrentDeviceLogout::class   => AuthifyLogActionEnum::CurrentDeviceLogout,
        Failed::class                => AuthifyLogActionEnum::Failed,
        Lockout::class               => AuthifyLogActionEnum::Lockout,
        Login::class                 => AuthifyLogActionEnum::Login,
        Logout::class                => AuthifyLogActionEnum::Logout,
        OtherDeviceLogout::class     => AuthifyLogActionEnum::OtherDeviceLogout,
        PasswordReset::class         => AuthifyLogActionEnum::PasswordReset,
        PasswordResetLinkSent::class => AuthifyLogActionEnum::PasswordResetLinkSent,
        Registered::class            => AuthifyLogActionEnum::Registered,
        Validated::class             => AuthifyLogActionEnum::Validated,
        Verified::class              => AuthifyLogActionEnum::Verified,
    ];


    public function __construct(
        protected AuthifyLogger $logger,
        protected Repository $config,
        protected Application $app,
    ) {}

    /**
     * The events this recorder listens for.
     *
     * @return list<class-string>
     */
    public function listen(): array
    {
        return array_keys($this->actions);
    }

    /**
     * Record the event.
     */
    public function record(object $event): void
    {
        $action = $this->actions[$event::class] ?? null;

        if (null === $action) {
            return;
        }

        // The user is taken from the typed event property rather than the
        // event as a whole, so credentials carried by Attempting, Failed and
        // Lockout are never read and can never reach the log.
        $user = property_exists($event, 'user') && is_object($event->user) ? $event->user : null;

        $this->notify($action, $user);

        $request = $this->app->make('request');

        $this->logger->record(new Entry(
            timestamp: Carbon::now()->getTimestamp(),
            action: $action->value,
            userId: $this->resolveUserId($user),
            ipAddress: $request->ip() ?? '0.0.0.0',
            ipCountry: $this->resolveCountry(),
            userAgent: (string) ($request->userAgent() ?? ''),
        ));
    }

    protected function resolveUserId(?object $user): ?int
    {
        if ( ! $user instanceof Authenticatable) {
            return null;
        }

        $key = $user->getAuthIdentifier();

        return is_numeric($key) ? (int) $key : null;
    }

    /**
     * The country header is only meaningful behind a proxy that sets it, so it
     * is normalised to two uppercase letters and otherwise discarded.
     */
    protected function resolveCountry(): string
    {
        $fallback = $this->config->string('authify-log.country_fallback', 'XX');
        $header = $this->config->get('authify-log.country_header');

        if ( ! is_string($header) || '' === $header) {
            return $fallback;
        }

        $value = $this->app->make('request')->header($header);

        if ( ! is_string($value)) {
            return $fallback;
        }

        $country = mb_strtoupper($value);

        return 1 === preg_match('/^[A-Z]{2}$/', $country) ? $country : $fallback;
    }

    protected function notify(AuthifyLogActionEnum $action, ?object $user): void
    {
        /** @var array<string, class-string|null> $map */
        $map = $this->config->array('authify-log.notifications', []);
        $notification = $map[$action->name] ?? null;

        if (null === $notification || null === $user) {
            return;
        }

        // Notifying must never break the authentication flow it observes, so
        // the failure is handed to the logger's exception handler.
        $this->logger->rescue(fn() => $this->app->make(NotificationDispatcher::class)
            ->send($user, new $notification()));
    }
}

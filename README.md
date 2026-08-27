# Laravel Authify Log

**A logging utility for user authentication activities in Laravel.**

Records every Laravel authentication event — logins, logouts, failed attempts, lockouts, password resets, registrations and verifications — together with the IP address, country and user agent behind them.

The architecture follows [Laravel Pulse](https://github.com/laravel/pulse): recorders capture events, an **ingest** driver gets them out of the request, and a **storage** driver persists them.

## Requirements

- PHP `^8.4`
- Laravel 11 or 12
- Redis, if you use the `redis` ingest driver

## Installation

```bash
composer require misaf/laravel-authify-log
```

Then run the installer, which publishes the config file and migration and offers to migrate:

```bash
php artisan authify-log:install
```

Or do it by hand:

```bash
php artisan vendor:publish --tag="authify-log-config"
php artisan vendor:publish --tag="authify-log-migrations"
php artisan vendor:publish --tag="authify-log-translations"
php artisan migrate
```

The migration also runs automatically if you never publish it.

## Usage

Nothing else is required. The `Authentication` recorder subscribes to Laravel's authentication events on boot, and every one of them is recorded:

| Event | Action |
| --- | --- |
| `Attempting` | `Attempting` |
| `Authenticated` | `Authenticated` |
| `CurrentDeviceLogout` | `CurrentDeviceLogout` |
| `Failed` | `Failed` |
| `Lockout` | `Lockout` |
| `Login` | `Login` |
| `Logout` | `Logout` |
| `OtherDeviceLogout` | `OtherDeviceLogout` |
| `PasswordReset` | `PasswordReset` |
| `PasswordResetLinkSent` | `PasswordResetLinkSent` |
| `Registered` | `Registered` |
| `Validated` | `Validated` |
| `Verified` | `Verified` |

Credentials carried by `Attempting`, `Failed` and `Lockout` are never read, so a plaintext password can never reach the log.

### Reading a user's logs

Add the trait to your authenticatable:

```php
use Misaf\LaravelAuthifyLog\Traits\HasAuthifyLog;

class User extends Authenticatable
{
    use HasAuthifyLog;
}
```

```php
$user->authifyLogs;        // every log, newest last
$user->latestAuthifyLog;   // most recent
$user->oldestAuthifyLog;   // first ever

$user->latestAuthifyLog->action->getLabel(); // "Successful Login"
```

### How an entry reaches the database

```
auth event ──▶ Recorder ──▶ AuthifyLogger buffer ──▶ Ingest ──▶ Storage ──▶ authify_logs
                                    (per request)
```

Entries are buffered for the lifetime of the request and handed to the ingest driver when it ends — on `terminate`, at the end of an artisan command, and on every queue worker loop.

#### Ingest drivers

Set with `AUTHIFY_LOG_INGEST_DRIVER`:

| Driver | What it does | Needs |
| --- | --- | --- |
| `storage` (default) | Writes the buffer to storage as the request ends | Nothing |
| `redis` | Appends the buffer to a Redis stream for `authify-log:work` to digest | Redis + a worker |
| `null` | Observes the events but records nothing | Nothing |

Use `redis` when the login endpoint is hot enough that you do not want to pay for the insert in-request.

#### Storage drivers

`database` is the only driver, writing to the table named by `storage.database.table` — which the model reads too, so the two can never disagree. Swap either half in your own service provider:

```php
use Misaf\LaravelAuthifyLog\Contracts\Ingest;
use Misaf\LaravelAuthifyLog\Contracts\Storage;

$this->app->bind(Storage::class, MyStorage::class);
$this->app->bind(Ingest::class, MyIngest::class);
```

### Running the worker

With the `redis` ingest driver, run a worker to digest the stream into storage. It also trims storage every ten minutes:

```bash
php artisan authify-log:work
```

Run it under Supervisor or Horizon. After deploying, tell any running worker to pick up the new code:

```bash
php artisan authify-log:restart
```

To digest once and exit — useful in a deploy hook or a scheduled command:

```bash
php artisan authify-log:work --stop-when-empty
```

To delete everything in storage:

```bash
php artisan authify-log:clear
```

On the `storage` and `null` ingest drivers there is nothing to digest, so the worker is a no-op — except for its storage trimming, which is worth scheduling either way.

### Retention

Storage is trimmed to `AUTHIFY_LOG_KEEP` (`7 days` by default) by the work command. Without a worker, schedule the trim yourself:

```php
use Misaf\LaravelAuthifyLog\Facades\Authify;

Schedule::daily()->call(fn () => Authify::trim());
```

The Redis stream is trimmed to `AUTHIFY_LOG_INGEST_KEEP` by a `[1, 1000]` lottery on ingest, so a stream nobody is digesting cannot grow without bound.

### The Authify facade

```php
Authify::ingest();          // flush the buffer into the ingest driver
Authify::digest();          // move ingested entries into storage
Authify::trim();            // apply the retention window
Authify::purge();           // delete everything
Authify::stopRecording();   // and startRecording()
Authify::ignore(fn () => ...);            // run without recording
Authify::filter(fn (Entry $entry) => ...); // drop entries before they are ingested
```

Nothing the package does may break the application it observes: recorder and ingest failures are reported through your exception handler rather than thrown. Storage failures inside the worker do propagate, so a broken digest is visible.

### Writing your own recorder

A recorder implements `Contracts\Recorder`: it names the events it wants and turns each one into an `Entry`.

```php
use Misaf\LaravelAuthifyLog\Contracts\Recorder;
use Misaf\LaravelAuthifyLog\Entry;
use Misaf\LaravelAuthifyLog\AuthifyLogger;

class ApiTokenUsage implements Recorder
{
    public function __construct(protected AuthifyLogger $logger) {}

    public function listen(): array
    {
        return [TokenAuthenticated::class];
    }

    public function record(object $event): void
    {
        $this->logger->record(new Entry(
            timestamp: now()->getTimestamp(),
            action: AuthifyLogActionEnum::Authenticated->value,
            userId: $event->user->getAuthIdentifier(),
            ipAddress: request()->ip() ?? '0.0.0.0',
            ipCountry: 'XX',
            userAgent: (string) request()->userAgent(),
        ));
    }
}
```

Add it to the `recorders` array in the config file; set an entry to `false` to disable one.

### Login notifications

Out of the box a `LoginNotification` is mailed on a successful login — nothing to implement on your model. Which actions notify, and with what, is decided entirely by the `notifications` array in the config file.

The greeting resolves the user's name from `name`, `username` or `email`, in that order. Override it for models that keep it elsewhere:

```php
use Misaf\LaravelAuthifyLog\Facades\Authify;

// In a service provider's boot():
Authify::user(fn (User $user) => $user->full_name);
```

Point `authify-log.password_reset_route` at your own named route to get the "Reset your password" button; if the route does not exist the button is omitted.

Map any action to any notification — or to `null` to send nothing — in the `notifications` array of the config file.

### Displaying the country

`ip_country` stores an ISO 3166-1 alpha-2 code taken from the `CF-IPCountry` header, defaulting to `XX`. Only trust it if your app really is behind a proxy that sets it; set `AUTHIFY_LOG_COUNTRY_HEADER=null` otherwise.

To render it as a flag, pull in [blade-country-flags](https://github.com/stijnvanouplines/blade-country-flags) yourself:

```bash
composer require stijnvanouplines/blade-country-flags
```

```blade
<x-country-flag :country="$log->ip_country" />
```

## Configuration

See [`config/authify-log.php`](config/authify-log.php) for the full, commented list. The most useful keys:

| Key | Default | Purpose |
| --- | --- | --- |
| `enabled` | `true` | Turn all logging off |
| `model` | `AuthifyLog::class` | Swap in your own model |
| `ingest.driver` | `storage` | `storage`, `redis` or `null` |
| `ingest.buffer` | `5000` | Entries buffered before an early flush |
| `ingest.trim.keep` | `7 days` | Retention for the Redis stream |
| `storage.driver` | `database` | Where entries are persisted |
| `storage.database.chunk` | `1000` | Rows per insert |
| `storage.database.trim.keep` | `7 days` | Retention for `authify_logs` |
| `recorders` | `Authentication` | Recorders to register |
| `queue` | `laravel-authify-log` | Queue for login notifications |
| `foreign_key` | `user_id` | Column linking a log to its user |
| `password_reset_route` | `password.request` | Route linked from the notification |

## Localization

English and Persian translations ship with the package under the `authify-log::` namespace. Publish them with the `authify-log-translations` tag to customise.

## Testing

```bash
composer test          # Pest: unit, feature and architecture suites
composer analyse       # PHPStan level 10
composer format        # Pint
```

## Contributing

Contributions, issues and feature requests are welcome. Please add tests for anything you change and make sure `composer test` and `composer analyse` both pass.

## License

Open-sourced under the [MIT license](LICENSE).

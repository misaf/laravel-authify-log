# Changelog

All notable changes to `laravel-authify-log` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-08-27

Initial release.

### Added

- Records all thirteen Laravel authentication events — `Attempting`,
  `Authenticated`, `CurrentDeviceLogout`, `Failed`, `Lockout`, `Login`,
  `Logout`, `OtherDeviceLogout`, `PasswordReset`, `PasswordResetLinkSent`,
  `Registered`, `Validated` and `Verified` — with the IP address, country and
  user agent behind each one.
- An architecture modelled on Laravel Pulse: a recorder captures events, the
  `AuthifyLogger` buffers them for the lifetime of the request, an ingest
  driver gets them out of that request, and a storage driver persists them.
- Ingest drivers: `storage` (write-through, the default), `redis` (buffers on
  a Redis stream for a worker to digest) and `null` (records nothing), resolved
  from `authify-log.ingest.driver` through the `Contracts\Ingest` binding.
- `Contracts\Storage` with a `database` driver writing to `authify_logs`, and
  chunked inserts for large batches.
- `Contracts\Recorder` and the `Authentication` recorder, registered from the
  `recorders` config array — add your own alongside it.
- The `Authify` facade: `ingest()`, `digest()`, `trim()`, `purge()`,
  `filter()`, `stopRecording()`, `ignore()` and `rescue()`.
- Retention for both halves: `storage.database.trim.keep` applied by the work
  command, and `ingest.trim.keep` applied to the Redis stream by a `[1, 1000]`
  lottery on ingest, so an undigested stream cannot grow without bound.
- `authify-log:work` (with `--stop-when-empty`), `authify-log:restart` and
  `authify-log:clear` commands.
- `HasAuthifyLog` trait exposing `authifyLogs`, `latestAuthifyLog` and
  `oldestAuthifyLog` relations.
- Configurable per-action notifications, with a `LoginNotification` mailed on
  successful login. `Contracts\ResolvesUsers` decides the name to greet the
  user by — `name`, `username` or `email` by default, or whatever
  `Authify::user(...)` is given.
- `authify-log:install` command, plus publishable config, migrations and
  translations, via `spatie/laravel-package-tools`.
- English and Persian translations under the `authify-log::` namespace.
- Model factory with `forUser()` and `action()` states.

### Security

- Credentials carried by the `Attempting`, `Failed` and `Lockout` events are
  never read, so a plaintext password cannot reach the log.
- The country header is validated as two letters before being stored, and can
  be disabled for applications that do not sit behind a proxy that sets it.
- Stream payloads are unserialized with `allowed_classes` restricted to `Entry`,
  and anything else on the stream is dropped rather than instantiated.
- Recording never breaks the request it observes: recorder and ingest failures
  are reported through the application's exception handler instead of thrown.

[1.0.0]: https://github.com/misaf/laravel-authify-log/releases/tag/1.0.0

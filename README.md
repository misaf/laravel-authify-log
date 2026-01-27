# Laravel Authify Log

**A logging utility for user authentication activities in Laravel.**

## Features

- Log all user authentication events (login, logout, failed attempts, password resets, etc.).
- Includes user metadata such as IP address, browser, and location.
- Supports [Blade Country Flags](https://github.com/stijnvanouplines/blade-country-flags) to display user origin.
- Easy to integrate into any Laravel 10+ application.
- Fully configurable via Laravel service provider.
- Built with [Spatie Laravel Package Tools](https://spatie.be/docs/laravel-package-tools) for easy package management.

## Requirements

- PHP ^8.3  
- Laravel 10+  
- Dependencies:
  - `stijnvanouplines/blade-country-flags ^1.0.6`
  - `spatie/laravel-package-tools ^1.92.4`

## Installation

Install via Composer:

```bash
composer require misaf/laravel-authify-log
Usage
Publish the service provider (optional for customization):

bash
Copy code
php artisan vendor:publish --provider="Misaf\AuthifyLog\Providers\AuthifyLogServiceProvider"
Log authentication events automatically:

The package hooks into Laravel’s authentication events by default:

Login → Logged automatically

Logout → Logged automatically

Failed login attempts → Logged automatically

Password resets → Logged automatically

Optional: Display user location with flags:

In Blade views, you can use country flags:

blade
Copy code
@countryFlag($user->country_code)
Database Factories & Seeders:

You can use the included factories and seeders for testing:

bash
Copy code
php artisan db:seed --class=Misaf\\AuthifyLog\\Database\\Seeders\\AuthifyLogSeeder
Testing
If you have tests in the tests/ folder, run them using:

bash
Copy code
composer test
(You may need Pest or PHPUnit configured depending on your setup.)

Contributing
Contributions, issues, and feature requests are welcome!
Feel free to fork the repository and submit pull requests.

License
This package is open-sourced under the MIT license.
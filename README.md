# Laravel Notification Center

A **headless** notification center for Laravel — zero coupling to any UI package.
It provides the storage, delivery, and management layer for in-app notifications,
leaving the presentation entirely up to you.

> **Status:** Scaffolding only. No features are implemented yet.

## Requirements

- PHP `^8.2`
- Laravel 12 or 13 (`illuminate/contracts: ^12.0 || ^13.0`)

## Installation

```bash
composer require xjoc/laravel-notification-center
```

The service provider is auto-discovered via package discovery.

### Publishing the config

```bash
php artisan vendor:publish --tag="notification-center-config"
```

## Development

This package uses [spatie/laravel-package-tools](https://github.com/spatie/laravel-package-tools)
and is tested against Laravel 12 and 13 on PHP 8.2–8.4.

```bash
composer install      # install dependencies
composer pint         # format code (Laravel preset)
composer phpstan      # static analysis (larastan, max level)
composer test         # run the Pest test suite
```

## Package structure

```
src/
├── Commands/        Artisan commands
├── Concerns/        Reusable traits
├── Contracts/       Interfaces
├── Enums/           Enumerations
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Listeners/       Event listeners
├── Models/          Eloquent models
├── Notifications/   Notification classes
├── Support/         Internal helpers
├── Templates/       Notification templates
└── Facades/         Facades
config/              notification-center.php
database/migrations/ Schema migrations
routes/
├── admin.php        Administrative routes
└── user.php         End-user routes
```

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.

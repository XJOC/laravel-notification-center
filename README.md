# Laravel Notification Center

A **headless** notification center for Laravel — zero coupling to any UI package.
It provides a central **gateway**, template management, per-channel admin settings,
and per-user opt-out preferences for Laravel notifications, leaving presentation
entirely up to you.

Notifications still flow through Laravel's native notification system. This package
inserts itself as a **gateway** on the `NotificationSending` event: it decides whether
a given notification may go out on a given channel, and injects the rendered template
body (and subject) at send time.

## Concept: two tiers

The center deliberately keeps two kinds of notification types side by side:

- **Tier 1 — coded types.** Declared in `config/notification-center.php` under `types`
  and synced into the database with `php artisan notification-center:sync`. These are
  the types your application code dispatches (e.g. `order.confirmed`, `otp.sent`).
  Re-syncing updates their structure but never their enabled/disabled state.
- **Tier 2 — admin types.** Created at runtime through the admin API. They are owned by
  the admin and are **never** touched by `notification-center:sync`.

Every type belongs to a **category** (`essential`, `transactional`, `alerts`,
`marketing`). The category drives gateway behaviour — see below.

## Requirements

- PHP `^8.2`
- Laravel 12 or 13 (`illuminate/contracts: ^12.0 || ^13.0`)

## Installation

```bash
composer require xjoc/laravel-notification-center
```

The service provider is auto-discovered via package discovery.

### One-command install

```bash
php artisan notification-center:install
```

This publishes the config, runs the package migrations, and runs an initial sync of
your coded types. It is equivalent to running, in order:

```bash
php artisan vendor:publish --tag="notification-center-config"
php artisan migrate
php artisan notification-center:sync
```

### Manual config publish

```bash
php artisan vendor:publish --tag="notification-center-config"
```

### Required host tables (caveats)

This package leans on Laravel's native notification channels. Two things must exist
in the **host** application, not in this package:

- **`database` channel** requires the standard `notifications` table. Create it with:

  ```bash
  php artisan notifications:table
  php artisan migrate
  ```

- **`whatsapp` channel** is **not** shipped with a driver. The center renders and
  injects the WhatsApp template body (a plain string), but you must register a host
  channel driver named `whatsapp` (via `Notification::extend('whatsapp', ...)`) that
  knows how to actually deliver it. Without a driver, WhatsApp sends will fail at the
  Laravel layer, not in this package.

## Low-touch integration (not zero-touch)

There is no magic. To route one of your existing notifications through the center, do
two small things:

1. Implement the `NotifiableNotification` contract.
2. Use the `HasNotificationCenter` trait.

```php
<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Xjoc\NotificationCenter\Concerns\HasNotificationCenter;
use Xjoc\NotificationCenter\Contracts\NotifiableNotification;

final class OrderConfirmedNotification extends Notification implements NotifiableNotification
{
    use HasNotificationCenter;

    public function __construct(
        private string $orderId,
        private string $total,
    ) {}

    public function notificationType(): string
    {
        return 'order.confirmed';
    }

    /** @return array<string, mixed> */
    public function notificationVariables(object $notifiable): array
    {
        return [
            'customer_name' => $notifiable->name,
            'order_id'      => $this->orderId,
            'total'         => $this->total,
        ];
    }
}
```

Then dispatch it the normal Laravel way:

```php
$user->notify(new OrderConfirmedNotification($orderId, $total));
```

The trait provides `via()` (resolves to the type's supported channels), and
`toMail()`, `toDatabase()`/`toArray()`, and `toWhatsapp()`, all of which read the
template the gateway injected at send time.

### Template injection + override-wins

At send time the gateway renders the type's stored template for the target channel and
injects it into the notification via the single unified method:

```php
public function injectTemplate(string $channel, string $rendered, ?string $subject = null): void;
```

- **mail** uses the rendered subject + body (`MailMessage`).
- **database** stores `['subject' => ?string, 'body' => string]`.
- **whatsapp** uses the rendered body string.

**Override-wins rule.** If you define your own `toMail()` / `toDatabase()` /
`toWhatsapp()` on the notification, **your method wins** — PHP method resolution puts
your class method ahead of the trait. The center will still gate the send; it just
won't supply the body. This is intentional: the trait is a convenience, not a cage.

> If a channel has no injected template and no override, the trait throws
> `MissingTemplateException`. This means: the type exists and is allowed, but no
> template row exists for that channel. Create one via the admin Template API.

## The central gateway (order of decisions)

For **every** outgoing notification, the package listens on
`Illuminate\Notifications\Events\NotificationSending` and runs this exact sequence per
channel:

1. **Not ours?** If the notification does not implement `NotifiableNotification`, allow
   it untouched.
2. **Unknown type?** If the type key is not registered in the DB, allow it untouched.
3. **Essential bypass.** If the type's category is `essential`, inject the template and
   **always** allow — ignoring the master switch, the channel setting, and the user's
   opt-out. (See "Essential protection" under Security.)
4. **Master switch.** If the type is disabled (`is_enabled = false`), block.
5. **Admin channel setting.** If the per-channel `NotificationSetting` is disabled,
   block.
6. **User opt-out.** If the user has opted out of this `(type, channel)`, block.
7. **Allow.** Render + inject the template, then allow the send.

Blocking a single channel does not block the others — the decision is made per channel.

### Categories and the essential bypass

| Category        | Gateway behaviour                                              |
|-----------------|---------------------------------------------------------------|
| `essential`     | **Bypasses the gateway** and is **force-locked** + force-enabled. Cannot be disabled by admin or opted out by users (e.g. OTP). |
| `transactional` | Fully gated (master switch, channel setting, user opt-out).   |
| `alerts`        | Fully gated.                                                  |
| `marketing`     | Fully gated.                                                  |

## Configuration

`config/notification-center.php`:

| Key                       | Default                              | Purpose                                                                 |
|---------------------------|--------------------------------------|-------------------------------------------------------------------------|
| `admin_middleware`        | `['auth:sanctum', 'role:admin']`     | Middleware for the admin routes.                                        |
| `user_middleware`         | `['auth:sanctum']`                   | Middleware for the user routes.                                         |
| `route_prefix`            | `'notification-center'`              | URL prefix for both route groups.                                       |
| `user_model`              | `'App\Models\User'`                  | String class name (never autoloaded at config-parse time).              |
| `notifiable_models`       | `['App\Models\User']`                | **Allowlist** of models permitted as dispatch recipients.               |
| `channels`                | `['mail', 'database', 'whatsapp']`   | Channels the center knows about. Custom channels are allowed.           |
| `cache.enabled`           | `true`                               | Toggle the lookup cache. When `false`, every read hits the DB directly. |
| `cache.store`             | `null`                               | Cache store name (`null` = default store).                              |
| `cache.ttl`               | `3600`                               | Cache TTL in seconds.                                                   |
| `cache.prefix`            | `'notification-center'`              | Cache key prefix.                                                       |
| `templates.escape_html`   | `true`                               | Escape variable **values** on HTML channels.                            |
| `templates.html_channels` | `['mail']`                           | Channels treated as HTML for escaping.                                  |
| `templates.on_missing_var`| `'empty'`                            | `'empty'` (blank) or `'throw'` (`MissingVariableException`) for unknown template variables. |
| `types`                   | see below                            | Tier-1 coded types synced via `notification-center:sync`.               |

### Declaring coded types

```php
'types' => [
    'order.confirmed' => [
        'name'      => 'Order Confirmed',
        'category'  => 'transactional',
        'channels'  => ['mail', 'whatsapp'],
        'locked'    => false,
        'variables' => ['customer_name', 'order_id', 'total'],
    ],
    'otp.sent' => [
        'name'      => 'OTP Sent',
        'category'  => 'essential',
        'channels'  => ['whatsapp'],
        'locked'    => true,
        'variables' => ['otp_code', 'expires_in'],
    ],
],
```

## Artisan commands

| Command                        | Description                                                                                          |
|--------------------------------|------------------------------------------------------------------------------------------------------|
| `notification-center:install`  | Publish config, run migrations, then sync. One-shot setup.                                          |
| `notification-center:sync`     | Sync the coded `types` from config into the DB.                                                     |

`notification-center:sync` guarantees:

- **Admin-created rows are never touched** (matched by `created_by = 'admin'`).
- For existing config rows, only **structural** fields are updated (name, category,
  supported channels, variables, lock) — the `is_enabled` master switch is **preserved**.
- New types are created with `is_enabled = true` and `created_by = 'config'`.
- Default per-channel `NotificationSetting` rows are created when missing; existing ones
  are never modified.
- **Templates are never touched.**
- Essential types are force-locked.
- The command is idempotent and flushes the relevant caches afterwards.

## Sending notifications

### Direct dispatch (your code)

Use Laravel's notification system as usual once your notification implements the
contract and uses the trait:

```php
$user->notify(new OrderConfirmedNotification($orderId, $total));
```

### Manual dispatch via the facade

When you don't have a dedicated notification class, dispatch by type key:

```php
use Xjoc\NotificationCenter\Facades\NotificationCenter;

NotificationCenter::send(
    typeKey: 'order.confirmed',
    notifiables: $user,                 // a single notifiable or an iterable of them
    variables: ['customer_name' => 'Sam', 'order_id' => '42', 'total' => '$10'],
    channels: null,                     // null = all supported channels for the type
);
```

Signature:

```php
public function send(
    string $typeKey,
    iterable|object $notifiables,
    array $variables = [],
    ?array $channels = null,
): void;
```

This builds a `GenericNotification` and dispatches it through the gateway — so admin
settings and user opt-outs still apply.

### Event binding (no code changes at the call site)

Bind a domain event to one or more notification types and the center will dispatch
automatically when that event fires. Your event must implement
`ProvidesNotificationContext`:

```php
<?php

declare(strict_types=1);

namespace App\Events;

use Xjoc\NotificationCenter\Contracts\ProvidesNotificationContext;

final class OrderWasConfirmed implements ProvidesNotificationContext
{
    public function __construct(private object $customer, private string $orderId) {}

    /** @return iterable<int, object> */
    public function notificationRecipients(): iterable
    {
        return [$this->customer];
    }

    /** @return array<string, mixed> */
    public function notificationVariables(): array
    {
        return ['customer_name' => $this->customer->name, 'order_id' => $this->orderId];
    }
}
```

Then bind the event to a type through the admin **Event Bindings** API. When the event
fires, the center sends the bound type(s) to the event's recipients with the event's
variables. The service provider registers listeners for every active binding at boot
(guarded against the DB not being ready, e.g. during `migrate`).

> Because bindings are read at boot, a binding created at runtime takes effect on the
> next boot. Flush the binding cache after creating one (the admin API does this) and,
> in a long-running worker, plan for the listener registration to occur on the next
> process start.

## API endpoints

All routes are JSON. The prefix is `route_prefix` from config (default
`notification-center`). Route-model binding resolves `{type}` to a `NotificationType`
and `{binding}` to a `NotificationEventBinding`.

### Admin API

Group prefix: `{route_prefix}/admin` · middleware: `admin_middleware` · route-name
prefix: `notification-center.admin.`

| Method   | URI                                          | Name                          | Action                                                                 |
|----------|----------------------------------------------|-------------------------------|------------------------------------------------------------------------|
| `GET`    | `types`                                      | `types.index`                 | List all types (with settings).                                        |
| `POST`   | `types`                                      | `types.store`                 | Create an admin type. Essential forces lock + enabled. Creates settings. `201`. |
| `PATCH`  | `types/{type}`                               | `types.update`                | Update name / supported channels / enabled. Disabling essential or locked → `422`. |
| `POST`   | `types/{type}/dispatch`                      | `types.dispatch`              | Dispatch this type to resolved recipients. `202`.                      |
| `GET`    | `types/{type}/templates`                     | `types.templates.index`       | List the type's templates.                                             |
| `PUT`    | `types/{type}/templates/{channel}`           | `types.templates.update`      | Upsert a template for a channel (`201` created / `200` updated).       |
| `GET`    | `types/{type}/event-bindings`                | `types.event-bindings.index`  | List the type's event bindings.                                        |
| `POST`   | `types/{type}/event-bindings`                | `types.event-bindings.store`  | Bind an event class to the type. `201`.                                |
| `DELETE` | `event-bindings/{binding}`                   | `event-bindings.destroy`      | Remove a binding. `204`.                                               |
| `GET`    | `settings`                                   | `settings.index`              | Overview of all types + their per-channel settings.                    |

Dispatch request body shape:

```json
{
  "recipients": { "model": "App\\Models\\User", "ids": [1, 2, 3] },
  "variables": { "customer_name": "Sam" },
  "channels": ["mail"]
}
```

`recipients.model` must be present in the `notifiable_models` allowlist (validated),
otherwise the request is rejected with `422`. `channels` must be a subset of the type's
supported channels; `null`/omitted dispatches on all supported channels.

### User API

Group prefix: `{route_prefix}/user` · middleware: `user_middleware` · route-name
prefix: `notification-center.user.`

| Method | URI                                  | Name                  | Action                                                              |
|--------|--------------------------------------|-----------------------|--------------------------------------------------------------------|
| `GET`  | `preferences`                        | `preferences.index`   | The authenticated user's per-type, per-channel opt-out state.      |
| `PUT`  | `preferences/{type}/{channel}`       | `preferences.update`  | Set `opted_out` for a `(type, channel)`. Essential type → `403`.   |

`preferences` returns, for each type and supported channel, an entry of the form
`{ type_id, type_key, channel, opted_out, locked }` where `locked` is true for essential
types. Updating an essential type is forbidden (`403`).

## Caching

Type, setting, template, supported-channel, and event-binding lookups are cached to
keep the gateway cheap on the hot path. Behaviour:

- Controlled by `cache.enabled`, `cache.store`, `cache.ttl`, and `cache.prefix`.
- When `cache.enabled = false`, every read hits the DB directly and all cache forgets
  are no-ops.
- Mutations through the admin API and `notification-center:sync` perform **targeted**
  cache forgets (per type, per settings, per templates, and event bindings) so stale
  decisions never leak.
- User opt-out lookups are cached per `(notifiable, type, channel)` and forgotten when
  the user updates that preference.

## Security

- **Output escaping.** The template renderer supports two token forms: escaped
  `{{ key }}` and raw `{!! key !!}`. On HTML channels (`templates.html_channels`,
  default `['mail']`) escaped values are passed through Laravel's `e()` helper when
  `templates.escape_html` is enabled. Use `{!! ... !!}` only for values you trust to be
  safe HTML. Unknown variables resolve to empty (or throw, per
  `templates.on_missing_var`).
- **Essential protection.** Essential types are force-locked and force-enabled, bypass
  the gateway entirely, and **cannot** be disabled by an admin (`422`) or opted out of
  by a user (`403`). This guarantees critical messages such as OTP codes are always
  delivered.
- **Recipient allowlist.** Manual/admin dispatch can only target models listed in
  `notifiable_models`. Anything else throws/`422`s before a notification is built.

## v1 caveats (honest limitations)

- **Mail is simplified.** The mail channel renders subject + body as a `MailMessage`
  with a single body line. There are **no action buttons** in v1; the rendered template
  body is the message body.
- **WhatsApp needs a host driver.** This package renders/injects the WhatsApp body but
  does not deliver it — register a `whatsapp` channel driver in your app.
- **Database channel needs the host's `notifications` table.** Run
  `php artisan notifications:table` and migrate.
- **Event bindings register at boot.** Bindings created at runtime take effect on the
  next process boot.

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
├── Commands/        Artisan commands (install, sync)
├── Concerns/        HasNotificationCenter trait
├── Contracts/       Interfaces (NotifiableNotification, NotificationChannel, ProvidesNotificationContext)
├── Enums/           NotificationCategory, Channel, CreatedBy
├── Exceptions/      MissingTemplateException, MissingVariableException
├── Facades/         NotificationCenter
├── Http/
│   ├── Controllers/ Admin + User controllers
│   ├── Requests/    Form requests
│   └── Resources/   API resources
├── Listeners/       NotificationGatewayListener, EventBindingListener
├── Models/          Eloquent models
├── Notifications/   GenericNotification
├── Support/         Cache, PreferenceResolver, RecipientResolver
└── Templates/       TemplateRenderer
config/              notification-center.php
database/migrations/ Schema migrations
routes/
├── admin.php        Administrative routes
└── user.php         End-user routes
```

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.

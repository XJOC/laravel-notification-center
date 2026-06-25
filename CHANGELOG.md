# Changelog

All notable changes to `xjoc/laravel-notification-center` are documented here.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-06-25

Initial stable release. A headless notification center built on top of Laravel's
native notification system — no coupling to any UI package.

### Added

- **Central gateway** — a `NotificationSending` listener that applies admin
  settings, per-user preferences, and the `essential`-category bypass in a fixed
  order (any single disable wins; essential is never gated).
- **Two tiers** — config-synced *coded* types and admin-created *dynamic* types,
  both dispatched through `GenericNotification` via `NotificationCenterManager`.
- **Low-touch integration** — the `NotifiableNotification` contract plus the
  `HasNotificationCenter` trait; a developer's own channel method always wins
  over an injected template (override-wins).
- **Channel driver system** — the `NotificationChannel` contract and
  `ChannelRegistry`; built-in `mail`, `database`, and `whatsapp` drivers each own
  their own rendering and escaping. Channels are developer-registered (config or
  a service provider); a read-only `GET admin/channels` endpoint exposes the
  registered keys as the admin-pickable list.
- **Template engine** — `{{ value }}` (HTML-escaped on mail) and `{!! value !!}`
  (raw), with configurable missing-variable behavior (`empty` | `throw`).
- **WhatsApp transport** — the `WhatsappTransport` contract and a structured,
  typed `WhatsappMessage` (text in v1; file/location/buttons reserved and throw
  until a future release). Ships no provider integration; the default transport
  throws a clear exception until you bind your own.
- **Triggers** — manual dispatch (the `NotificationCenter` facade and the admin
  API) and binding to existing Laravel events via `ProvidesNotificationContext`.
- **Persistence & caching** — five Eloquent models with migrations; cached
  type/setting/template/preference lookups with targeted invalidation on every
  mutation.
- **HTTP API** — admin and user JSON endpoints (Form Requests + API Resources,
  config-driven routes/middleware) with server-side `essential` protection
  (403/422).
- **Artisan** — `notification-center:install` and `notification-center:sync`
  (sync never touches `is_enabled`, templates, or admin-created rows).

### Requirements

- PHP `^8.2`; Laravel 12 or 13 (`illuminate/contracts: ^12.0 || ^13.0`).

[1.0.0]: https://github.com/xJOC/laravel-notification-center/releases/tag/v1.0.0

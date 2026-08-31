# Changelog

All notable changes to `laravel-security` will be documented in this file.

## Laravel-Security v1.2.2 - 2026-08-31

### ⚠️ Breaking / behavior changes

- The package routes (`security.run-audit`, `security.run-outdated`) now require the `web,auth` middleware by default. Configurable via `SECURITY_ROUTES_MIDDLEWARE`.
- Pruning is enabled by default: audit records older than `SECURITY_RETENTION_DAYS` (90) are deleted starting with the first scheduled run. Set `SECURITY_PRUNE_ENABLED=false` or `SECURITY_RETENTION_DAYS=0` to opt out.
- `AuditService` now has constructor dependencies — resolve it via the container instead of `new AuditService()`.
- If you published the config file, re-publish it to get the new keys: `php artisan vendor:publish --tag="security-config" --force`

### New features

- `security:prune` command with a daily schedule honoring `SECURITY_RETENTION_DAYS`
- English/German translations for notifications and the dashboard (`vendor:publish --tag="security-translations"`)
- `SECURITY_NOTIFY_MIN_SEVERITY`: only notify for findings at or above a severity threshold
- CI mode: `security:audit --fail-on=<severity>` (exit 1, fails closed on unavailable sources) and `--no-notifications`
- `SECURITY_NOTIFY_ONLY_NEW`: only notify when new vulnerabilities appeared vs. the previous audit, with a "still open" counter
- OSV.dev API driver (`SECURITY_AUDIT_DRIVER=api`): audits lockfiles without the composer/npm binaries — ideal for shared hosting
- `security:audit` prints the active driver; dashboard buttons show a loading spinner

### Fixes

- Notification dispatch consolidated into a `SecurityNotifier` service (identical channel behavior)
- Stale per-run state on reused command instances no longer fails later runs in long-lived processes
- Empty `SECURITY_ROUTES_MIDDLEWARE` falls back to `web,auth` instead of removing all middleware

## Laravel-Security v.1.0.2 - 2026-03-19

### Laravel 10 Version

adds migration up void
removes tailwind cdn
update readme

## Laravel-Security v1.1.1 - 2026-03-19

### Laravel 11 Version

adds migration up void
removes tailwind cdn
update readme

## Laravel-Security v1.2.1 - 2026-03-19

### Laravel 12 Version

adds migration up void
removes tailwind cdn
update readme

## Laravel-Security v.1.3 - 2026-03-18

- adds migration up void
- remove support for Laravel 10
- adds support for Laravel 13
- removes tailwind cdn
- update dependencies
- update readme
- update run-tests.yml

## Laravel-Security v.1.2 - 2026-02-17

- removes useless migration
- adds migration down

## Laravel-Security v.1.1 - 2026-01-27

You can now choose whether the database notification should also be sent to the user's email address.

- added database_mail to selection
- temporary removes ExampleTest.php
- extended README.md
- added url, better description, ray to composer.json

## Laravel-Security v.1.0 - 2026-01-26

First release of Laravel Security

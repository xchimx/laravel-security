# Laravel Security Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xchimx/laravel-security.svg?style=flat-square)](https://packagist.org/packages/xchimx/laravel-security)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/xchimx/laravel-security/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/xchimx/laravel-security/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/xchimx/laravel-security/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/xchimx/laravel-security/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/xchimx/laravel-security.svg?style=flat-square)](https://packagist.org/packages/xchimx/laravel-security)

![](resources/images/logo.png)

A Laravel package for automated monitoring of security vulnerabilities and outdated packages in Composer and NPM dependencies.

## Installation

You can install the package via composer:

```bash
composer require xchimx/laravel-security
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="security-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="security-config"
```

You can publish the views, or you add this line to your app.css:

```css
@source '../../../../vendor/xchimx/laravel-security/resources/views/**/*.blade.php';
```

Customize the `config/security.php` file according to your requirements or set the corresponding ENV variables:

```env
# App Info
APP_NAME=MyApp
APP_URL=https://myapp.com

# Security Audit
SECURITY_AUDIT_ENABLED=true
SECURITY_AUDIT_TIME=02:00
SECURITY_AUDIT_COMPOSER=true
SECURITY_AUDIT_NPM=true
SECURITY_AUDIT_DRIVER=cli

# Outdated Checks
SECURITY_OUTDATED_ENABLED=true
SECURITY_OUTDATED_TIME=03:00
SECURITY_OUTDATED_COMPOSER=true
SECURITY_OUTDATED_NPM=true

# Notifications
SECURITY_NOTIFY_USER_ID=1
SECURITY_NOTIFICATIONS_USER_MODEL=App\Models\User
SECURITY_NOTIFICATIONS_ROUTE=admin.security
SECURITY_NOTIFY_MAIL=true
SECURITY_NOTIFY_DATABASE=true
SECURITY_NOTIFY_DATABASE_MAIL=false
SECURITY_NOTIFY_SLACK=false
SECURITY_NOTIFY_MIN_SEVERITY=low
SECURITY_NOTIFY_ONLY_NEW=false
SECURITY_MAIL_TO=admin@example.com
SLACK_BOT_USER_OAUTH_TOKEN=xxx-xxx-xxx
SLACK_BOT_USER_DEFAULT_CHANNEL="#security-alerts"

# Routes
SECURITY_ROUTES_MIDDLEWARE=web,auth

# Storage
SECURITY_RETENTION_DAYS=90
SECURITY_PRUNE_ENABLED=true
SECURITY_PRUNE_TIME=04:00
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="security-views"
```

Optionally, you can publish the translations (English and German included, English is the default) using

```bash
php artisan vendor:publish --tag="security-translations"
```

Notifications and the dashboard follow the application locale (`config('app.locale')`). Locales without translations fall back to the application's fallback locale (`config('app.fallback_locale')`, English by default).

## Usage

The package automatically registers the following tasks in the Laravel Scheduler:

- **Security Audit**: Daily at 02:00 (configurable)
- **Outdated Check**: Weekly on Mondays at 3:00 a.m. (configurable)
- **Prune**: Daily at 04:00 (configurable), deletes audit records older than `SECURITY_RETENTION_DAYS` days

Ensure that the Laravel Scheduler is running:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Manual Usage

```bash
# Perform security audit
php artisan security:audit

# Check Composer only
php artisan security:audit --composer

# Check NPM only
php artisan security:audit --npm

# Check for outdated packages
php artisan security:outdated

# Check Composer only
php artisan security:outdated --composer

# Check NPM only
php artisan security:outdated --npm

# Prune audit records older than the retention period
php artisan security:prune
```

### Shared hosting / audits without CLI binaries

By default the audit runs the `composer audit` and `npm audit` binaries. On servers without these binaries (e.g. shared hosting) you can switch to the API driver, which reads `composer.lock` / `package-lock.json` and queries the [OSV.dev](https://osv.dev) vulnerability database instead:

```env
SECURITY_AUDIT_DRIVER=api
```

Notes on the API driver:

- Requires outgoing HTTPS to `api.osv.dev`.
- The lockfiles are expected in `base_path()`; a different directory can be set via `SECURITY_AUDIT_WORKING_DIRECTORY`.
- Development versions (`dev-main`, `1.x-dev`) cannot be matched and are skipped (logged).
- `package-lock.json` must use the v2/v3 format (npm 7+).

### CI usage

In CI pipelines the audit can fail the build and skip notifications:

```bash
php artisan security:audit --fail-on=high --no-notifications
```

`--fail-on` accepts `low`, `medium`, `high` or `critical` and exits with code 1 when a vulnerability at or above the given severity is found. Vulnerabilities with an unknown severity always count as a match, and the run also fails when an enabled audit source is not available.

### Dashboard Component

Integrate the Security Dashboard Component into your Blade views:

```blade
<x-security-security-dashboard />
```

The dashboard buttons post to package routes that trigger audit runs. These routes use the `web` and `auth` middleware by default; adjust via:

```env
SECURITY_ROUTES_MIDDLEWARE=web,auth
```

Note: the Blade component itself is rendered by your own route — protect that page with your own middleware. Prior to this version the package routes were unauthenticated; the `auth` default is a deliberate breaking change.

### Programmatic Access

```php
use Xchimx\LaravelSecurity\Models\SecurityAudit;

// Retrieve latest Composer audit
$audit = SecurityAudit::getLatestAudit('composer');

// Latest outdated check for NPM
$outdated = SecurityAudit::getLatestOutdated('npm');

// All audits with issuesen
$issues = SecurityAudit::withIssues()->get();

// Audits from the last 7 days
$recent = SecurityAudit::where('executed_at', '>=', now()->subDays(7))->get();
```

## Notifications

### Database notifications

Database notifications are sent to the user ID configured in `SECURITY_NOTIFY_USER_ID`. If the user has an email address and `SECURITY_NOTIFY_DATABASE_MAIL` is set to `true`, the notification is also sent to that address

When database notifications are enabled, notifications are stored in the `notifications` table. This requires the standard Laravel notifications migration:

```env
SECURITY_NOTIFY_USER_ID=1 #User ID
SECURITY_NOTIFICATIONS_USER_MODEL=App\Models\User #User Model
SECURITY_NOTIFY_DATABASE=true #Set database notification to enabled
SECURITY_NOTIFY_DATABASE_MAIL=false #User receives database notification without email. Set to “true” if an email should also be sent.
```

```bash
php artisan notifications:table
php artisan migrate
```



### Email notifications

Emails are sent to the address configured in `SECURITY_MAIL_TO`. You can separate multiple addresses with commas:

```env
SECURITY_MAIL_TO=admin@example.com,security@example.com
```

### Slack notifications

Configure your Slack token:

```env
SECURITY_NOTIFY_SLACK=true
SLACK_BOT_USER_OAUTH_TOKEN=xxx-xxx-xxx
SLACK_BOT_USER_DEFAULT_CHANNEL="#security-alerts"
```

### Severity threshold

Audit notifications can be limited to findings with a minimum severity (`low`, `medium`, `high`, `critical`):

```env
SECURITY_NOTIFY_MIN_SEVERITY=high
```

Audit results are always stored in the database; the threshold only controls whether a notification is sent. Vulnerabilities with an unknown severity always trigger a notification.

### Only notify about new vulnerabilities

By default every audit with findings sends a notification, even when nothing changed since the last run. To only get notified when *new* vulnerabilities appear (compared to the previous audit of the same source):

```env
SECURITY_NOTIFY_ONLY_NEW=true
```

Notifications then list the new findings in detail and add a counter for known vulnerabilities that are still open. Vulnerabilities are matched by CVE, or by package + title when no CVE is available. When combined with `SECURITY_NOTIFY_MIN_SEVERITY`, the threshold applies to the new findings.

## Data model

The `security_audits` table stores:

- `type`: 'audit' or 'outdated'
- `source`: 'composer' or 'npm'
- `results`: JSON with details about the issues found
- `vulnerabilities_count`: Number of security vulnerabilities
- `outdated_count`: Number of outdated packages
- `has_issues`: Boolean flag
- `raw_output`: Raw output of the command
- `executed_at`: Time of execution

## Requirements

- PHP ^8.3
- Laravel ^12.0
- Composer (installed on the server; not required with `SECURITY_AUDIT_DRIVER=api`)
- NPM (Optional if NPM packages are to be checked; not required with `SECURITY_AUDIT_DRIVER=api`)

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Tobias Schottstädt](https://www.schottstaedt.net)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Views

Dashboard
![](resources/images/dashboard.png)

Mail Notification
![](resources/images/mail.png)

Slack Notification Audit
![](resources/images/slack_audit.png)

Slack Notification Outdated
![](resources/images/slack_outdated.png)

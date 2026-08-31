<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Audit Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the security audit settings for composer and npm.
    |
    */

    'audit' => [
        'enabled' => env('SECURITY_AUDIT_ENABLED', true),
        'time' => env('SECURITY_AUDIT_TIME', '02:00'),
        'composer' => env('SECURITY_AUDIT_COMPOSER', true),
        'npm' => env('SECURITY_AUDIT_NPM', true),

        // "cli" runs the composer/npm binaries, "api" queries OSV.dev
        // using the lockfiles only (no binaries required, e.g. shared hosting).
        'driver' => env('SECURITY_AUDIT_DRIVER', 'cli'),

        // Directory containing composer.lock / package-lock.json (defaults to base_path())
        'working_directory' => env('SECURITY_AUDIT_WORKING_DIRECTORY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Outdated Packages Check Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the outdated packages check settings.
    |
    */

    'outdated' => [
        'enabled' => env('SECURITY_OUTDATED_ENABLED', true),
        'time' => env('SECURITY_OUTDATED_TIME', '03:00'),
        'composer' => env('SECURITY_OUTDATED_COMPOSER', true),
        'npm' => env('SECURITY_OUTDATED_NPM', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Middleware for the package routes (security.run-audit / security.run-outdated).
    | These routes trigger audit runs, so they should stay behind auth.
    |
    */

    'routes' => [
        'middleware' => array_filter(array_map(
            'trim',
            explode(',', (string) env('SECURITY_ROUTES_MIDDLEWARE', 'web,auth'))
        )) ?: ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which notification channels to use and their settings.
    |
    */

    'notifications' => [
        'user_model' => env('SECURITY_NOTIFICATIONS_USER_MODEL', 'App\Models\User'),
        'user_id' => env('SECURITY_NOTIFY_USER_ID', 1),
        'route' => env('SECURITY_NOTIFICATIONS_ROUTE', 'admin.security'),
        'channels' => [
            'mail' => env('SECURITY_NOTIFY_MAIL', true),
            'database' => env('SECURITY_NOTIFY_DATABASE', true),
            'database_mail' => env('SECURITY_NOTIFY_DATABASE_MAIL', true),
            'slack' => env('SECURITY_NOTIFY_SLACK', false),
        ],

        // List of email addresses to notify
        'mail_to' => env('SECURITY_MAIL_TO', 'admin@example.com'),

        // Minimum severity that triggers an audit notification: low, medium, high, critical
        // Vulnerabilities with an unknown severity always trigger a notification.
        'min_severity' => env('SECURITY_NOTIFY_MIN_SEVERITY', 'low'),

        // Only send audit notifications when new vulnerabilities appeared
        // compared to the previous audit of the same source.
        'only_new' => env('SECURITY_NOTIFY_ONLY_NEW', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how audit results are stored.
    |
    */

    'storage' => [
        // Number of days to keep audit records (0 disables pruning)
        'retention_days' => env('SECURITY_RETENTION_DAYS', 90),

        // Scheduled daily pruning of old audit records
        'prune_enabled' => env('SECURITY_PRUNE_ENABLED', true),
        'prune_time' => env('SECURITY_PRUNE_TIME', '04:00'),
    ],
];

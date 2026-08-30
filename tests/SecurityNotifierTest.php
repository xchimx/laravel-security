<?php

use Illuminate\Support\Facades\Notification;
use Xchimx\LaravelSecurity\Models\SecurityAudit;
use Xchimx\LaravelSecurity\Notifications\OutdatedPackagesNotification;
use Xchimx\LaravelSecurity\Notifications\SecurityAuditNotification;
use Xchimx\LaravelSecurity\Services\SecurityNotifier;

function makeAuditWithSeverity(string $severity): SecurityAudit
{
    return SecurityAudit::factory()->create([
        'results' => [
            [
                'package' => 'vendor/package',
                'title' => 'Example vulnerability',
                'cve' => 'CVE-2026-0002',
                'severity' => $severity,
                'link' => null,
            ],
        ],
        'vulnerabilities_count' => 1,
        'has_issues' => true,
    ]);
}

it('sends an audit notification with the default configuration', function () {
    Notification::fake();

    $sent = app(SecurityNotifier::class)->sendAuditNotification([makeAuditWithSeverity('medium')]);

    expect($sent)->toBeTrue();
    Notification::assertSentOnDemand(SecurityAuditNotification::class);
});

it('skips the audit notification when all channels are disabled', function () {
    Notification::fake();
    config()->set('security.notifications.channels', [
        'mail' => false,
        'database' => false,
        'database_mail' => false,
        'slack' => false,
    ]);

    $sent = app(SecurityNotifier::class)->sendAuditNotification([makeAuditWithSeverity('critical')]);

    expect($sent)->toBeFalse();
    Notification::assertNothingSent();
});

it('skips the audit notification when all findings are below the minimum severity', function () {
    Notification::fake();
    config()->set('security.notifications.min_severity', 'high');

    $sent = app(SecurityNotifier::class)->sendAuditNotification([makeAuditWithSeverity('medium')]);

    expect($sent)->toBeFalse();
    Notification::assertNothingSent();
});

it('sends the audit notification when a finding meets the minimum severity', function () {
    Notification::fake();
    config()->set('security.notifications.min_severity', 'high');

    $sent = app(SecurityNotifier::class)->sendAuditNotification([makeAuditWithSeverity('critical')]);

    expect($sent)->toBeTrue();
    Notification::assertSentOnDemand(SecurityAuditNotification::class);
});

it('sends the audit notification for findings with unknown severity despite a threshold', function () {
    Notification::fake();
    config()->set('security.notifications.min_severity', 'critical');

    $sent = app(SecurityNotifier::class)->sendAuditNotification([makeAuditWithSeverity('not-a-severity')]);

    expect($sent)->toBeTrue();
    Notification::assertSentOnDemand(SecurityAuditNotification::class);
});

it('skips the audit notification when only_new is active and nothing changed', function () {
    Notification::fake();
    config()->set('security.notifications.only_new', true);

    makeAuditWithSeverity('critical');
    $second = makeAuditWithSeverity('critical');

    $sent = app(SecurityNotifier::class)->sendAuditNotification([$second]);

    expect($sent)->toBeFalse();
    Notification::assertNothingSent();
});

it('sends the audit notification with diff details when new vulnerabilities appear', function () {
    Notification::fake();
    config()->set('security.notifications.only_new', true);

    makeAuditWithSeverity('critical');

    $second = SecurityAudit::factory()->create([
        'results' => [
            [
                'package' => 'vendor/package',
                'title' => 'Example vulnerability',
                'cve' => 'CVE-2026-0002',
                'severity' => 'critical',
                'link' => null,
            ],
            [
                'package' => 'vendor/other',
                'title' => 'Fresh vulnerability',
                'cve' => 'CVE-2026-9999',
                'severity' => 'high',
                'link' => null,
            ],
        ],
        'vulnerabilities_count' => 2,
        'has_issues' => true,
    ]);

    $sent = app(SecurityNotifier::class)->sendAuditNotification([$second]);

    expect($sent)->toBeTrue();
    Notification::assertSentOnDemand(SecurityAuditNotification::class, function (SecurityAuditNotification $notification, array $channels, object $notifiable): bool {
        $mail = $notification->toMail($notifiable);
        $lines = implode("\n", array_map(strval(...), $mail->introLines));

        return str_contains($lines, '1 new vulnerabilities found')
            && str_contains($lines, 'vendor/other')
            && str_contains($lines, '1 known vulnerabilities are still open')
            && ! str_contains($lines, '- [critical] vendor/package');
    });
});

it('applies the severity threshold to new findings only when only_new is active', function () {
    Notification::fake();
    config()->set('security.notifications.only_new', true);
    config()->set('security.notifications.min_severity', 'high');

    makeAuditWithSeverity('critical');

    $second = SecurityAudit::factory()->create([
        'results' => [
            [
                'package' => 'vendor/package',
                'title' => 'Example vulnerability',
                'cve' => 'CVE-2026-0002',
                'severity' => 'critical',
                'link' => null,
            ],
            [
                'package' => 'vendor/other',
                'title' => 'Minor new vulnerability',
                'cve' => 'CVE-2026-8888',
                'severity' => 'low',
                'link' => null,
            ],
        ],
        'vulnerabilities_count' => 2,
        'has_issues' => true,
    ]);

    $sent = app(SecurityNotifier::class)->sendAuditNotification([$second]);

    expect($sent)->toBeFalse();
    Notification::assertNothingSent();
});

it('sends outdated notifications regardless of the minimum severity', function () {
    Notification::fake();
    config()->set('security.notifications.min_severity', 'critical');

    $outdated = SecurityAudit::factory()->create([
        'type' => 'outdated',
        'results' => [
            ['package' => 'vendor/package', 'current' => '1.0.0', 'latest' => '2.0.0'],
        ],
        'outdated_count' => 1,
        'has_issues' => true,
    ]);

    $sent = app(SecurityNotifier::class)->sendOutdatedNotification([$outdated]);

    expect($sent)->toBeTrue();
    Notification::assertSentOnDemand(OutdatedPackagesNotification::class);
});

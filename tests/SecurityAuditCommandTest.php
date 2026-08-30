<?php

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;
use Xchimx\LaravelSecurity\Models\SecurityAudit;
use Xchimx\LaravelSecurity\Notifications\SecurityAuditNotification;

function fakeComposerAudit(string $severity): void
{
    $auditJson = json_encode([
        'advisories' => [
            'vendor/package' => [
                [
                    'title' => 'Example vulnerability',
                    'cve' => 'CVE-2026-0003',
                    'severity' => $severity,
                    'link' => 'https://example.com/advisory',
                ],
            ],
        ],
    ]);

    Process::fake([
        'composer --version' => Process::result('Composer version 2.8.0'),
        'composer audit --format=json' => Process::result($auditJson),
    ]);

    config()->set('security.audit.npm', false);
}

it('keeps exit code zero without the fail-on option', function () {
    Notification::fake();
    fakeComposerAudit('critical');

    $this->artisan('security:audit')->assertSuccessful();

    Notification::assertSentOnDemand(SecurityAuditNotification::class);
});

it('fails when a vulnerability meets the fail-on threshold', function () {
    Notification::fake();
    fakeComposerAudit('critical');

    $this->artisan('security:audit', ['--fail-on' => 'high'])->assertFailed();
});

it('succeeds when all vulnerabilities are below the fail-on threshold', function () {
    Notification::fake();
    fakeComposerAudit('low');

    $this->artisan('security:audit', ['--fail-on' => 'high'])->assertSuccessful();
});

it('rejects an invalid fail-on value without running the audit', function () {
    Notification::fake();
    fakeComposerAudit('critical');

    $this->artisan('security:audit', ['--fail-on' => 'not-a-severity'])->assertFailed();

    expect(SecurityAudit::query()->count())->toBe(0);
});

it('suppresses notifications with the no-notifications option but stores the audit', function () {
    Notification::fake();
    fakeComposerAudit('critical');

    $this->artisan('security:audit', ['--no-notifications' => true])->assertSuccessful();

    Notification::assertNothingSent();
    expect(SecurityAudit::query()->count())->toBe(1);
});

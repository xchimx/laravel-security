<?php

use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Notifications\AnonymousNotifiable;
use Xchimx\LaravelSecurity\Models\SecurityAudit;
use Xchimx\LaravelSecurity\Notifications\OutdatedPackagesNotification;
use Xchimx\LaravelSecurity\Notifications\SecurityAuditNotification;

uses(InteractsWithViews::class);

function makeAuditWithIssues(): SecurityAudit
{
    return SecurityAudit::factory()->create([
        'results' => [
            [
                'package' => 'vendor/package',
                'title' => 'Example vulnerability',
                'cve' => 'CVE-2026-0001',
                'severity' => 'high',
                'link' => null,
            ],
        ],
        'vulnerabilities_count' => 1,
        'has_issues' => true,
    ]);
}

it('renders the audit mail in english by default', function () {
    $mail = (new SecurityAuditNotification([makeAuditWithIssues()]))->toMail(new AnonymousNotifiable);

    expect($mail->subject)->toContain('Security Vulnerabilities Detected')
        ->and($mail->introLines[0])->toContain('security vulnerabilities');
});

it('renders the audit mail in german when the locale is de', function () {
    app()->setLocale('de');

    $mail = (new SecurityAuditNotification([makeAuditWithIssues()]))->toMail(new AnonymousNotifiable);

    expect($mail->subject)->toContain('Sicherheitslücken erkannt')
        ->and($mail->introLines[0])->toContain('Sicherheitslücken');
});

it('falls back to english for unsupported locales', function () {
    app()->setLocale('fr');

    $mail = (new SecurityAuditNotification([makeAuditWithIssues()]))->toMail(new AnonymousNotifiable);

    expect($mail->subject)->toContain('Security Vulnerabilities Detected');
});

it('renders the outdated mail in german when the locale is de', function () {
    app()->setLocale('de');

    $outdated = SecurityAudit::factory()->create([
        'type' => 'outdated',
        'results' => [
            ['package' => 'vendor/package', 'current' => '1.0.0', 'latest' => '2.0.0'],
        ],
        'outdated_count' => 1,
        'has_issues' => true,
    ]);

    $mail = (new OutdatedPackagesNotification([$outdated]))->toMail(new AnonymousNotifiable);

    expect($mail->subject)->toContain('Bericht über veraltete Pakete');
});

it('renders the dashboard component in english by default', function () {
    $view = $this->blade('<x-security-security-dashboard />');

    $view->assertSee('Monitor security vulnerabilities and outdated packages');
});

it('renders the dashboard component in german when the locale is de', function () {
    app()->setLocale('de');

    $view = $this->blade('<x-security-security-dashboard />');

    $view->assertSee('Überwachung von Sicherheitslücken und veralteten Paketen');
});

<?php

use Xchimx\LaravelSecurity\Models\SecurityAudit;
use Xchimx\LaravelSecurity\Support\AuditDiff;

function vulnerability(string $package, string $title, ?string $cve = null, string $severity = 'high'): array
{
    return [
        'package' => $package,
        'title' => $title,
        'cve' => $cve,
        'severity' => $severity,
        'link' => null,
    ];
}

it('treats every vulnerability as new for the first audit', function () {
    $audit = SecurityAudit::factory()->create([
        'results' => [vulnerability('vendor/a', 'Issue A', 'CVE-1')],
        'vulnerabilities_count' => 1,
        'has_issues' => true,
    ]);

    $diff = AuditDiff::forAudit($audit);

    expect($diff->new)->toHaveCount(1)
        ->and($diff->known)->toHaveCount(0)
        ->and($diff->hasNew())->toBeTrue();
});

it('recognizes unchanged vulnerabilities as known', function () {
    SecurityAudit::factory()->create([
        'results' => [vulnerability('vendor/a', 'Issue A', 'CVE-1')],
        'vulnerabilities_count' => 1,
        'has_issues' => true,
    ]);

    $current = SecurityAudit::factory()->create([
        'results' => [vulnerability('vendor/a', 'Issue A', 'CVE-1')],
        'vulnerabilities_count' => 1,
        'has_issues' => true,
    ]);

    $diff = AuditDiff::forAudit($current);

    expect($diff->new)->toHaveCount(0)
        ->and($diff->known)->toHaveCount(1)
        ->and($diff->hasNew())->toBeFalse();
});

it('matches vulnerabilities without a cve by package and title', function () {
    SecurityAudit::factory()->create([
        'results' => [vulnerability('vendor/a', 'Issue A')],
        'vulnerabilities_count' => 1,
        'has_issues' => true,
    ]);

    $current = SecurityAudit::factory()->create([
        'results' => [
            vulnerability('vendor/a', 'Issue A'),
            vulnerability('vendor/b', 'Issue B'),
        ],
        'vulnerabilities_count' => 2,
        'has_issues' => true,
    ]);

    $diff = AuditDiff::forAudit($current);

    expect($diff->known)->toHaveCount(1)
        ->and($diff->new)->toHaveCount(1)
        ->and($diff->new[0]['package'])->toBe('vendor/b');
});

it('compares only against audits of the same source', function () {
    SecurityAudit::factory()->create([
        'source' => 'npm',
        'results' => [vulnerability('vendor/a', 'Issue A', 'CVE-1')],
        'vulnerabilities_count' => 1,
        'has_issues' => true,
    ]);

    $current = SecurityAudit::factory()->create([
        'source' => 'composer',
        'results' => [vulnerability('vendor/a', 'Issue A', 'CVE-1')],
        'vulnerabilities_count' => 1,
        'has_issues' => true,
    ]);

    $diff = AuditDiff::forAudit($current);

    expect($diff->new)->toHaveCount(1)
        ->and($diff->known)->toHaveCount(0);
});

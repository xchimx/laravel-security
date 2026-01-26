<?php

namespace Xchimx\LaravelSecurity\Commands;

use Illuminate\Console\Command;
use Xchimx\LaravelSecurity\Commands\Concerns\RunsSecurityChecks;
use Xchimx\LaravelSecurity\Commands\Concerns\SendsSecurityNotifications;
use Xchimx\LaravelSecurity\Models\SecurityAudit;
use Xchimx\LaravelSecurity\Notifications\SecurityAuditNotification;
use Xchimx\LaravelSecurity\Services\AuditService;

class RunSecurityAuditCommand extends Command
{
    use RunsSecurityChecks;
    use SendsSecurityNotifications;

    protected $signature = 'security:audit
                                {--composer : Run only composer audit}
                                {--npm : Run only npm audit}';

    protected $description = 'Run security audit for composer and npm packages';

    public function handle(AuditService $auditService): int
    {
        $this->info('Running security audit...');

        /** @var array<int, SecurityAudit> $results */
        $results = [];

        // 1. Run Composer Audit
        $composerResult = $this->performCheck(
            tool: 'composer',
            configKey: 'security.audit.composer',
            availabilityCheck: fn () => $auditService->isComposerAvailable(),
            executionCallback: fn () => $auditService->runComposerAudit(),
            processMsg: 'Running composer audit...',
            issueMsgFormat: 'Found %s vulnerabilities in composer packages',
            successMsg: 'No vulnerabilities found in composer packages',
            countProp: 'vulnerabilities_count'
        );

        if ($composerResult) {
            $results[] = $composerResult;
        }

        // 2. Run NPM Audit
        $npmResult = $this->performCheck(
            tool: 'npm',
            configKey: 'security.audit.npm',
            availabilityCheck: fn () => $auditService->isNpmAvailable(),
            executionCallback: fn () => $auditService->runNpmAudit(),
            processMsg: 'Running npm audit...',
            issueMsgFormat: 'Found %s vulnerabilities in npm packages',
            successMsg: 'No vulnerabilities found in npm packages',
            countProp: 'vulnerabilities_count'
        );

        if ($npmResult) {
            $results[] = $npmResult;
        }

        // 3. Evaluate results and send notifications
        $hasIssues = collect($results)->contains('has_issues', true);

        if ($hasIssues && count($results) > 0) {
            $this->info('Sending notifications...');
            $this->sendConfiguredNotifications(
                fn (array $channels) => new SecurityAuditNotification($results, $channels)
            );
        }

        $this->info('Security audit completed!');

        return self::SUCCESS;
    }
}

<?php

namespace Xchimx\LaravelSecurity\Commands;

use Illuminate\Console\Command;
use Xchimx\LaravelSecurity\Commands\Concerns\RunsSecurityChecks;
use Xchimx\LaravelSecurity\Models\SecurityAudit;
use Xchimx\LaravelSecurity\Services\AuditService;
use Xchimx\LaravelSecurity\Services\SecurityNotifier;
use Xchimx\LaravelSecurity\Support\Severity;

class RunSecurityAuditCommand extends Command
{
    use RunsSecurityChecks;

    protected $signature = 'security:audit
                                {--composer : Run only composer audit}
                                {--npm : Run only npm audit}
                                {--fail-on= : Exit with code 1 when a vulnerability with this severity or higher is found (low, medium, high, critical)}
                                {--no-notifications : Do not send notifications}';

    protected $description = 'Run security audit for composer and npm packages';

    public function handle(AuditService $auditService, SecurityNotifier $notifier): int
    {
        $this->resetCheckState();

        $failOnThreshold = null;
        $failOnOption = $this->option('fail-on');

        if (is_string($failOnOption) && $failOnOption !== '') {
            $failOnThreshold = Severity::fromString($failOnOption);

            if ($failOnThreshold === Severity::Unknown) {
                $this->error("Invalid --fail-on value [{$failOnOption}]. Allowed values: low, medium, high, critical.");

                return self::FAILURE;
            }
        }

        $this->info('Running security audit...');
        $this->info(sprintf('Using audit driver [%s].', config('security.audit.driver', 'cli')));

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

        if ($hasIssues && count($results) > 0 && ! $this->option('no-notifications')) {
            if ($notifier->sendAuditNotification($results)) {
                $this->info('Notifications sent.');
            } else {
                $this->info('Notifications skipped (no usable channels, below severity threshold, or no new findings).');
            }
        }

        $this->info('Security audit completed!');

        if ($failOnThreshold instanceof Severity && $this->hasVulnerabilityMeeting($results, $failOnThreshold)) {
            $this->error("Failing: vulnerabilities at or above [{$failOnOption}] severity were found.");

            return self::FAILURE;
        }

        if ($failOnThreshold instanceof Severity && $this->sourceUnavailable) {
            $this->error('Failing: --fail-on is set but an enabled audit source was not available.');

            return self::FAILURE;
        }

        return $this->checkFailed ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<int, SecurityAudit>  $results
     */
    protected function hasVulnerabilityMeeting(array $results, Severity $threshold): bool
    {
        foreach ($results as $result) {
            if ($result->hasVulnerabilityMeeting($threshold)) {
                return true;
            }
        }

        return false;
    }
}

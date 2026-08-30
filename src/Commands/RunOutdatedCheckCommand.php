<?php

namespace Xchimx\LaravelSecurity\Commands;

use Illuminate\Console\Command;
use Xchimx\LaravelSecurity\Commands\Concerns\RunsSecurityChecks;
use Xchimx\LaravelSecurity\Models\SecurityAudit;
use Xchimx\LaravelSecurity\Services\OutdatedService;
use Xchimx\LaravelSecurity\Services\SecurityNotifier;

class RunOutdatedCheckCommand extends Command
{
    use RunsSecurityChecks;

    protected $signature = 'security:outdated
                                {--composer : Check only composer packages}
                                {--npm : Check only npm packages}';

    protected $description = 'Check for outdated composer and npm packages';

    public function handle(OutdatedService $outdatedService, SecurityNotifier $notifier): int
    {
        $this->resetCheckState();

        $this->info('Checking for outdated packages...');

        /** @var array<int, SecurityAudit> $results */
        $results = [];

        // 1. Run Composer Check
        $composerResult = $this->performCheck(
            tool: 'composer',
            configKey: 'security.outdated.composer',
            availabilityCheck: fn () => $outdatedService->isComposerAvailable(),
            executionCallback: fn () => $outdatedService->runComposerOutdated(),
            processMsg: 'Checking composer packages...',
            issueMsgFormat: 'Found %s outdated composer packages',
            successMsg: 'All composer packages are up to date',
            countProp: 'outdated_count'
        );

        if ($composerResult) {
            $results[] = $composerResult;
        }

        // 2. Run NPM Check
        $npmResult = $this->performCheck(
            tool: 'npm',
            configKey: 'security.outdated.npm',
            availabilityCheck: fn () => $outdatedService->isNpmAvailable(),
            executionCallback: fn () => $outdatedService->runNpmOutdated(),
            processMsg: 'Checking npm packages...',
            issueMsgFormat: 'Found %s outdated npm packages',
            successMsg: 'All npm packages are up to date',
            countProp: 'outdated_count'
        );

        if ($npmResult) {
            $results[] = $npmResult;
        }

        // 3. Evaluate results and send notifications
        $hasOutdated = collect($results)->contains('has_issues', true);

        if ($hasOutdated && count($results) > 0) {
            if ($notifier->sendOutdatedNotification($results)) {
                $this->info('Notifications sent.');
            } else {
                $this->info('Notifications skipped (no usable channels).');
            }
        }

        $this->info('Outdated check completed!');

        return $this->checkFailed ? self::FAILURE : self::SUCCESS;
    }
}

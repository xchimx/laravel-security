<?php

namespace Xchimx\LaravelSecurity\Commands\Concerns;

use Illuminate\Support\Facades\Config;
use Xchimx\LaravelSecurity\Models\SecurityAudit;

/**
 * @mixin \Illuminate\Console\Command
 */
trait RunsSecurityChecks
{
    /**
     * Executes a security check (npm or composer) and returns the result.
     *
     * @template TResult of SecurityAudit
     *
     * @param  string  $tool  'composer' or 'npm'
     * @param  string  $configKey  Config path (e.g., 'security.outdated.composer')
     * @param  callable(): bool  $availabilityCheck  Function that returns true if the tool is available
     * @param  callable(): TResult  $executionCallback  Function that executes the check and returns the result object
     * @param  string  $processMsg  Message displayed during execution
     * @param  string  $issueMsgFormat  Message displayed on issues (printf format with %s for the count)
     * @param  string  $successMsg  Message displayed on success
     * @param  string  $countProp  The property name containing the issue count (e.g., 'outdated_count')
     * @return TResult|null Returns the result object or null if skipped/failed
     */
    protected function performCheck(
        string $tool,
        string $configKey,
        callable $availabilityCheck,
        callable $executionCallback,
        string $processMsg,
        string $issueMsgFormat,
        string $successMsg,
        string $countProp
    ): ?SecurityAudit {
        // Logic: Check if this tool was explicitly selected OR if the other tool was NOT selected.
        // (Default behavior: run both if no specific flags are set)
        $otherTool = ($tool === 'composer') ? 'npm' : 'composer';
        $shouldRun = $this->option($tool) || ! $this->option($otherTool);

        // Check if config is enabled and CLI option matches
        if (! ($shouldRun && Config::get($configKey))) {
            return null;
        }

        // Check if the tool is available (e.g., is npm installed?)
        if (! $availabilityCheck()) {
            $this->warn(ucfirst($tool).' is not available');

            return null;
        }

        $this->info($processMsg);

        // Execute the callback
        $result = $executionCallback();

        // Evaluate the result
        if ($result->has_issues) {
            // Dynamic access to the property (e.g., outdated_count vs vulnerabilities_count).
            $count = $result->{$countProp};
            $this->warn(sprintf($issueMsgFormat, $count));
        } else {
            $this->info($successMsg);
        }

        return $result;
    }
}

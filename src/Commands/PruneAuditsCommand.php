<?php

namespace Xchimx\LaravelSecurity\Commands;

use Illuminate\Console\Command;
use Xchimx\LaravelSecurity\Models\SecurityAudit;

class PruneAuditsCommand extends Command
{
    protected $signature = 'security:prune';

    protected $description = 'Prune security audit records older than the configured retention period';

    public function handle(): int
    {
        $retentionDays = (int) config('security.storage.retention_days', 90);

        if ($retentionDays <= 0) {
            $this->info('Pruning is disabled (retention_days is not set).');

            return self::SUCCESS;
        }

        $deletedCount = SecurityAudit::query()
            ->where('executed_at', '<', now()->subDays($retentionDays))
            ->delete();

        $this->info("Pruned {$deletedCount} security audit records older than {$retentionDays} days.");

        return self::SUCCESS;
    }
}

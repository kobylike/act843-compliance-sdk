<?php

namespace GhanaCompliance\Act843SDK\Console\Commands;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use GhanaCompliance\Act843SDK\Models\IpReputation;
use GhanaCompliance\Act843SDK\Models\SecurityEvent;
use Illuminate\Console\Command;

class CompliancePurgeOldLogs extends Command
{
    protected $signature = 'compliance:purge {--dry-run : Show what would be deleted}';
    protected $description = 'Delete records older than retention policy';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $config = config('compliance.retention');

        $this->purgeTable(ComplianceLog::class, $config['compliance_logs'] ?? 90, $dryRun);
        $this->purgeTable(SecurityEvent::class, $config['security_events'] ?? 30, $dryRun);
        $this->purgeTable(IpReputation::class, $config['ip_reputations'] ?? 180, $dryRun);

        if ($dryRun) {
            $this->info('Dry run completed. Run without --dry-run to delete.');
        }
    }

    protected function purgeTable(string $model, int $days, bool $dryRun): void
    {
        $cutoff = now()->subDays($days);
        $query = $model::where('created_at', '<', $cutoff);
        $count = $query->count();

        if ($count === 0) return;

        $this->info("{$model}: {$count} records older than {$days} days");

        if (!$dryRun) {
            $query->delete();
            $this->info("Deleted.");
        }
    }
}

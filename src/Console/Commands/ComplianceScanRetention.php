<?php

namespace GhanaCompliance\Act843SDK\Console\Commands;

use GhanaCompliance\Act843SDK\Compliance\Modules\DataRetention\RetentionPolicyChecker;
use GhanaCompliance\Act843SDK\Compliance\Modules\DataRetention\OutdatedDataScanner;
use Illuminate\Console\Command;

class ComplianceScanRetention extends Command
{
    protected $signature = 'compliance:scan-retention {--table=} {--days=365}';
    protected $description = 'Scan data retention policies and outdated records';

    public function handle(RetentionPolicyChecker $retentionChecker, OutdatedDataScanner $outdatedScanner)
    {
        $this->info('Scanning retention policies...');
        $results = $retentionChecker->analyze();

        $this->table(
            ['Table', 'Oldest Record (days)', 'Policy (days)', 'Compliant'],
            collect($results)->map(fn($r) => [
                $r['table'],
                $r['oldest_record_days'],
                $r['retention_policy'] ?? 'None',
                $r['compliance'] ? '✅' : '❌',
            ])->toArray()
        );

        $table = $this->option('table');
        if ($table) {
            $this->info("Scanning outdated records in '{$table}'...");
            $outdated = $outdatedScanner->scan($table, 'created_at', (int)$this->option('days'));
            $this->line("Found {$outdated['outdated_count']} outdated records.");
        }

        return Command::SUCCESS;
    }
}

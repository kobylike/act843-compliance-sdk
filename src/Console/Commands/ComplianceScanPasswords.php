<?php

namespace GhanaCompliance\Act843SDK\Console\Commands;

use Illuminate\Console\Command;
use GhanaCompliance\Act843SDK\Compliance\Modules\AuthenticationSecurity\PasswordPolicyChecker;

class ComplianceScanPasswords extends Command
{
    protected $signature = 'compliance:scan-passwords {--deep : Run deep hash sampling} {--force : Skip confirmation prompt}';
    protected $description = 'Scan password policies and hashing algorithms for compliance';

    public function handle(PasswordPolicyChecker $checker)
    {
        $deep = $this->option('deep');
        $force = $this->option('force');

        if ($deep && !$force) {
            if (!$this->confirm('Deep scan will sample user password hashes. Continue?', true)) {
                return Command::SUCCESS;
            }
        }

        $this->info('Scanning password policies...');
        $results = $checker->analyze($deep);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Users', $results['total_users'] ?? 'N/A'],
                ['Weak Hashes', $results['weak_hashes']],
                ['Weak Policies', $results['weak_policies']],
                ['Common Passwords', $results['common_passwords']],
            ]
        );

        if (!empty($results['recommendations'])) {
            $this->warn('Recommendations:');
            foreach ($results['recommendations'] as $rec) {
                $this->line("- {$rec}");
            }
        }

        return Command::SUCCESS;
    }
}

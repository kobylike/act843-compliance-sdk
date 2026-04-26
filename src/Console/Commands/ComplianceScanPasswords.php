<?php

namespace GhanaCompliance\Act843SDK\Console\Commands;

use GhanaCompliance\Act843SDK\Compliance\Modules\AuthenticationSecurity\PasswordPolicyChecker;
use Illuminate\Console\Command;

class ComplianceScanPasswords extends Command
{
    protected $signature = 'compliance:scan-passwords {--deep : Run deep hash sampling (requires config approval)}';
    protected $description = 'Scan password policies and hashing algorithms for compliance';

    public function handle(PasswordPolicyChecker $checker)
    {
        $deep = $this->option('deep');

        if ($deep && !config('compliance.allow_deep_password_scan', false)) {
            $this->error('Deep scan is disabled. Set ALLOW_DEEP_PASSWORD_SCAN=true in .env');
            return Command::FAILURE;
        }

        if ($deep) {
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

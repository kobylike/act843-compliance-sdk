<?php

namespace GhanaCompliance\Act843SDK\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ComplianceInstallCommand extends Command
{
    protected $signature = 'compliance:install';
    protected $description = 'Install the Compliance SDK (config, migrations, event listeners)';

    public function handle()
    {
        $this->info('🚀 Installing Compliance SDK...');

        $this->publishConfig();
        $this->runMigrations();
        $this->registerEventListener();
        $this->registerProactivePasswordCheck();  // NEW
        $this->addMiddleware();
        $this->addRoutes();
        $this->setupEnv();
        $this->showNextSteps();

        return self::SUCCESS;
    }

    protected function publishConfig(): void
    {
        $target = config_path('compliance.php');
        if (File::exists($target) && !$this->confirm('Config file already exists. Overwrite?', false)) {
            $this->info('Skipped config file.');
            return;
        }
        $defaultConfig = $this->getDefaultConfig();
        File::put($target, $defaultConfig);
        $this->info('✅ Config published to config/compliance.php');
    }

    protected function runMigrations(): void
    {
        $this->call('vendor:publish', ['--tag' => 'compliance-migrations', '--force' => true]);
        $this->info('✅ Migrations published.');

        if ($this->confirm('Run compliance migrations now?', true)) {
            try {
                $this->call('migrate', ['--force' => true]);
                $this->info('✅ Migrations executed.');
            } catch (\Exception $e) {
                $this->warn('Migrations failed: ' . $e->getMessage());
                $this->warn('Please run "php artisan migrate" manually.');
            }
        } else {
            $this->warn('Please run migrations later: php artisan migrate');
        }
    }

    protected function registerEventListener(): void
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');
        if (!File::exists($providerPath)) {
            $this->error('AppServiceProvider not found.');
            return;
        }

        $content = File::get($providerPath);
        $useStatement = "use GhanaCompliance\\Act843SDK\\Listeners\\LogFailedLoginAttempt;";
        $listenerRegistration = <<<PHP

        Event::listen(
            \\Illuminate\\Auth\\Events\\Failed::class,
            LogFailedLoginAttempt::class
        );
PHP;

        if (!str_contains($content, 'LogFailedLoginAttempt')) {
            if (!str_contains($content, $useStatement)) {
                $content = str_replace(
                    'use Illuminate\Support\ServiceProvider;',
                    "use Illuminate\Support\ServiceProvider;\nuse Illuminate\Support\Facades\Event;\n$useStatement",
                    $content
                );
            }
            $pattern = '/public function boot\(\)\s*\{\s*/';
            if (preg_match($pattern, $content)) {
                $newContent = preg_replace($pattern, "$0$listenerRegistration\n", $content);
                File::put($providerPath, $newContent);
                $this->info('✅ Event listener registered in AppServiceProvider.');
            } else {
                $this->warn('Could not auto-register listener. Please add manually:');
                $this->line($listenerRegistration);
            }
        } else {
            $this->info('Event listener already registered.');
        }
    }

    /**
     * NEW: Register the proactive password hash detection listener.
     */
    protected function registerProactivePasswordCheck(): void
    {
        if (!$this->confirm('Enable proactive password hash detection? (recommended)', true)) {
            return;
        }

        $providerPath = app_path('Providers/AppServiceProvider.php');
        if (!File::exists($providerPath)) {
            $this->warn('AppServiceProvider not found. Please add manually:');
            $this->line("In the boot() method, add:\n\nuse App\\Models\\User;\nuse GhanaCompliance\\Act843SDK\\Listeners\\CheckPasswordHash;\n\nUser::saved(function (\$user) {\n    app(CheckPasswordHash::class)->handle(\$user);\n});");
            return;
        }

        $content = File::get($providerPath);
        $useUserModel = "use App\\Models\\User;";
        $useListener = "use GhanaCompliance\\Act843SDK\\Listeners\\CheckPasswordHash;";
        $registrationCode = <<<PHP

        // Proactive password hash detection (real-time)
        if (config('compliance.proactive_password_check', true)) {
            User::saved(function (\$user) {
                app(CheckPasswordHash::class)->handle(\$user);
            });
        }
PHP;

        if (!str_contains($content, 'CheckPasswordHash')) {
            // Add use statements if missing
            if (!str_contains($content, $useUserModel)) {
                // Insert after namespace
                if (preg_match('/^namespace .+;$/m', $content, $matches)) {
                    $pos = strpos($content, $matches[0]) + strlen($matches[0]);
                    $content = substr_replace($content, "\n\n$useUserModel\n$useListener", $pos, 0);
                }
            } elseif (!str_contains($content, $useListener)) {
                $content = str_replace($useUserModel, "$useUserModel\n$useListener", $content);
            }

            // Insert registration code inside boot()
            $pattern = '/public function boot\(\)\s*\{\s*/';
            if (preg_match($pattern, $content)) {
                $newContent = preg_replace($pattern, "$0$registrationCode\n", $content);
                File::put($providerPath, $newContent);
                $this->info('✅ Proactive password detection registered in AppServiceProvider.');
            } else {
                $this->warn('Could not auto-register proactive check. Please add manually:');
                $this->line($registrationCode);
            }
        } else {
            $this->info('Proactive password detection already registered.');
        }
    }

    protected function addMiddleware(): void
    {
        if (!$this->confirm('Add request tracking middleware? (recommended)', true)) {
            return;
        }

        $bootstrapPath = base_path('bootstrap/app.php');
        if (!File::exists($bootstrapPath)) {
            $this->warn('bootstrap/app.php not found. Please add middleware manually:');
            $this->line('$middleware->web(append: [\\GhanaCompliance\\Act843SDK\\Middleware\\TrackSecurityEvents::class])');
            return;
        }

        $content = File::get($bootstrapPath);
        $middlewareLine = '\\GhanaCompliance\\Act843SDK\\Middleware\\TrackSecurityEvents::class';
        if (!str_contains($content, $middlewareLine)) {
            $pattern = '/->withMiddleware\(function \(Middleware \$middleware\): void \{\s*/';
            $replacement = "$0        \$middleware->web(append: [$middlewareLine]);\n";
            if (preg_match($pattern, $content)) {
                $newContent = preg_replace($pattern, $replacement, $content);
                File::put($bootstrapPath, $newContent);
                $this->info('✅ Middleware added to bootstrap/app.php');
            } else {
                $this->warn('Could not auto-add middleware. Please add manually:');
                $this->line("Add within the `withMiddleware` closure: \$middleware->web(append: [$middlewareLine]);");
            }
        } else {
            $this->info('Middleware already present.');
        }
    }

    protected function addRoutes(): void
    {
        if (!$this->confirm('Add compliance dashboard routes? (requires Livewire)', true)) {
            return;
        }

        $routesPath = base_path('routes/web.php');
        $routeCode = "
// Compliance SDK routes (detection-only dashboard)
Route::middleware(['auth'])->group(function () {
    Route::get('/security-dashboard', \\GhanaCompliance\\Act843SDK\\Livewire\\SecurityDashboard::class)->name('compliance.dashboard');
    Route::get('/ip/{ip}', \\GhanaCompliance\\Act843SDK\\Livewire\\IpProfile::class)->name('compliance.ip.profile');
});
";
        $content = File::get($routesPath);
        if (!str_contains($content, 'security-dashboard')) {
            File::append($routesPath, $routeCode);
            $this->info('✅ Dashboard routes added to routes/web.php');
        } else {
            $this->info('Dashboard routes already exist.');
        }
    }

    protected function setupEnv(): void
    {
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            $this->warn('.env file not found. Please add manually:');
            $this->line('COMPLIANCE_ENABLED=true');
            $this->line('PASSWORD_MIN_LENGTH=12');
            $this->line('PASSWORD_COMPLEXITY=true');
            return;
        }

        $vars = [
            'COMPLIANCE_ENABLED' => 'true',
            'PASSWORD_MIN_LENGTH' => '12',
            'PASSWORD_COMPLEXITY' => 'true',
            'ALLOW_DEEP_PASSWORD_SCAN' => 'false',
            'COMPLIANCE_PROACTIVE_PASSWORD_CHECK' => 'true',
            'COMPLIANCE_REGULATOR_API_URL=http://127.0.0.1:8000/api/compliance-reports',
            'COMPLIANCE_REGULATOR_API_KEY' => 'your_api_kry_here'
        ];

        foreach ($vars as $key => $value) {
            if (!$this->envHasKey($key)) {
                File::append($envPath, "\n{$key}={$value}\n");
                $this->info("✅ Added {$key} to .env");
            }
        }
    }

    protected function envHasKey(string $key): bool
    {
        $env = File::get(base_path('.env'));
        return preg_match("/^{$key}=/m", $env) === 1;
    }

    protected function showNextSteps(): void
    {
        $this->newLine();
        $this->info('🎉 Compliance SDK installed successfully!');
        $this->newLine();
        $this->line('📌 Next steps:');
        $this->line('  1. Run: php artisan compliance:scan-passwords');
        $this->line('  2. Run: php artisan compliance:scan-retention');
        $this->line('  3. Visit /security-dashboard to see the dashboard (login required)');
        $this->newLine();
        $this->line('🔒 Detection only – no IP blocking, no enforcement.');
    }

    protected function getDefaultConfig(): string
    {
        return <<<'PHP'
<?php

return [
    'retention' => [
        'compliance_logs' => env('COMPLIANCE_RETENTION_DAYS', 90),
        'security_events' => env('SECURITY_EVENTS_RETENTION_DAYS', 30),
        'ip_reputations' => env('IP_REPUTATION_RETENTION_DAYS', 180),
    ],
    'password' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 12),
        'complexity' => env('PASSWORD_COMPLEXITY', true),
    ],
    'allow_deep_password_scan' => env('ALLOW_DEEP_PASSWORD_SCAN', false),
    'evaluation' => [
        'simulation_ips' => ['127.0.0.100', '127.0.0.101'],
    ],
    'report_email' => env('COMPLIANCE_REPORT_EMAIL', 'admin@example.com'),
    'anomaly_detection' => env('COMPLIANCE_ANOMALY_DETECTION', false),
    'proactive_password_check' => env('COMPLIANCE_PROACTIVE_PASSWORD_CHECK', true),
];
PHP;
    }
}

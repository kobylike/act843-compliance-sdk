<?php

namespace GhanaCompliance\Act843SDK\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
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
        $this->addMiddleware();
        $this->addRoutes();
        $this->setupEnv();
        $this->showNextSteps();

        return self::SUCCESS;
    }

    protected function publishConfig(): void
    {
        $target = config_path('compliance.php');
        if (File::exists($target)) {
            if (!$this->confirm('Config file already exists. Overwrite?', false)) {
                $this->info('Skipped config file.');
                return;
            }
        }
        $defaultConfig = $this->getDefaultConfig();
        File::put($target, $defaultConfig);
        $this->info('✅ Config published to config/compliance.php');
    }

    protected function runMigrations(): void
    {
        if ($this->confirm('Run compliance migrations now?', true)) {
            $this->call('migrate');
            $this->info('✅ Migrations executed.');
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
            // Add use statement after namespace
            if (!str_contains($content, $useStatement)) {
                $content = str_replace(
                    'use Illuminate\Support\ServiceProvider;',
                    "use Illuminate\Support\ServiceProvider;\nuse Illuminate\Support\Facades\Event;\n$useStatement",
                    $content
                );
            }
            // Insert listener inside boot()
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
            $replacement = "$0        \$middleware->web(append: [$middlewareLine::class]);\n";
            if (preg_match($pattern, $content)) {
                $newContent = preg_replace($pattern, $replacement, $content);
                File::put($bootstrapPath, $newContent);
                $this->info('✅ Middleware added to bootstrap/app.php');
            } else {
                $this->warn('Could not auto-add middleware. Please add manually:');
                $this->line("Add within the `withMiddleware` closure: \$middleware->web(append: [$middlewareLine::class]);");
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
];
PHP;
    }
}

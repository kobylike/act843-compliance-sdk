<?php

namespace GhanaCompliance\Act843SDK\Console\Commands;

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

        // 1. Publish config (copy from our stub)
        $this->publishConfig();

        // 2. Publish & run migrations (they already exist, but we ensure they are run)
        $this->runMigrations();

        // 3. Register event listener in AppServiceProvider
        $this->registerEventListener();

        // 4. Optionally add middleware
        $this->addMiddleware();

        // 5. Optionally add dashboard routes
        $this->addRoutes();

        // 6. Set environment variables
        $this->setupEnv();

        // 7. Show next steps
        $this->showNextSteps();

        return self::SUCCESS;
    }

    /**
     * Publish the compliance config file.
     */
    protected function publishConfig(): void
    {
        $target = config_path('compliance.php');
        if (File::exists($target)) {
            if (!$this->confirm('Config file already exists. Overwrite?', false)) {
                $this->info('Skipped config file.');
                return;
            }
        }

        // If we have a stub in the package, copy it; otherwise create a default.
        $stub = __DIR__ . '/../../../stubs/compliance.stub';
        if (File::exists($stub)) {
            File::copy($stub, $target);
        } else {
            // Create a default config file
            $defaultConfig = $this->getDefaultConfig();
            File::put($target, $defaultConfig);
        }
        $this->info('✅ Config published to config/compliance.php');
    }

    /**
     * Run migrations (they are already in database/migrations).
     */
    protected function runMigrations(): void
    {
        if ($this->confirm('Run compliance migrations now?', true)) {
            $this->call('migrate');
            $this->info('✅ Migrations executed.');
        } else {
            $this->warn('Please run migrations later: php artisan migrate');
        }
    }

    /**
     * Register the FailedLogin event listener in AppServiceProvider.
     */
    protected function registerEventListener(): void
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');
        if (!File::exists($providerPath)) {
            $this->error('AppServiceProvider not found.');
            return;
        }

        $content = File::get($providerPath);
        $listenerRegistration = "\n        Event::listen(\n            \\Illuminate\\Auth\\Events\\Failed::class,\n            \\App\\Listeners\\LogFailedLoginAttempt::class\n        );";

        if (!str_contains($content, 'LogFailedLoginAttempt')) {
            // Ensure Event facade is imported
            if (!str_contains($content, 'use Illuminate\Support\Facades\Event;')) {
                $content = str_replace(
                    'use Illuminate\Support\ServiceProvider;',
                    "use Illuminate\Support\ServiceProvider;\nuse Illuminate\Support\Facades\Event;",
                    $content
                );
            }
            // Insert listener registration inside boot() method
            $pattern = '/public function boot\(\)\s*\{\s*/';
            if (preg_match($pattern, $content)) {
                $newContent = preg_replace($pattern, "$0$listenerRegistration\n", $content);
                File::put($providerPath, $newContent);
                $this->info('✅ Event listener registered in AppServiceProvider.');
            } else {
                $this->warn('Could not automatically register listener. Please add manually:');
                $this->line($listenerRegistration);
            }
        } else {
            $this->info('Event listener already registered.');
        }
    }

    /**
     * Add the request tracking middleware to Kernel.php (optional).
     */
    protected function addMiddleware(): void
    {
        if (!$this->confirm('Add request tracking middleware to web group? (recommended)', true)) {
            return;
        }

        $kernelPath = app_path('Http/Kernel.php');
        if (!File::exists($kernelPath)) {
            $this->warn('Kernel.php not found. Add middleware manually:');
            $this->line('\\App\\Http\\Middleware\\TrackSecurityEvents::class');
            return;
        }

        $content = File::get($kernelPath);
        $middlewareLine = '\\App\\Http\\Middleware\\TrackSecurityEvents::class,';
        if (!str_contains($content, $middlewareLine)) {
            // Add to the web middleware group
            $pattern = "/'web' => \[\n(.*?)\n\s*\],/s";
            if (preg_match($pattern, $content, $matches)) {
                $newGroup = str_replace($matches[1], "        $middlewareLine\n$matches[1]", $matches[0]);
                $content = str_replace($matches[0], $newGroup, $content);
                File::put($kernelPath, $content);
                $this->info('✅ Middleware added to web group.');
            } else {
                $this->warn('Could not auto-add middleware. Please add manually:');
                $this->line($middlewareLine);
            }
        } else {
            $this->info('Middleware already present.');
        }
    }

    /**
     * Add dashboard routes to web.php (optional).
     */
    protected function addRoutes(): void
    {
        if (!$this->confirm('Add compliance dashboard routes? (requires Livewire)', true)) {
            return;
        }

        $routesPath = base_path('routes/web.php');
        $routeCode = "
// Compliance SDK routes (detection-only dashboard)
Route::middleware(['auth'])->group(function () {
    Route::get('/security-dashboard', \\App\\Livewire\\SecurityDashboard::class)->name('compliance.dashboard');
    Route::get('/ip/{ip}', \\App\\Livewire\\IpProfile::class)->name('compliance.ip.profile');
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

    /**
     * Add recommended environment variables.
     */
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
        $this->line('  3. Visit /security-dashboard to see the dashboard (if you added routes)');
        $this->newLine();
        $this->line('🔒 Detection only – no IP blocking, no enforcement.');
    }

    /**
     * Return the default configuration content.
     */
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

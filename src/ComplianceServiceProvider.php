<?php

namespace GhanaCompliance\Act843SDK;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Failed;
use Livewire\Livewire;
use GhanaCompliance\Act843SDK\Livewire\SecurityDashboard;
use GhanaCompliance\Act843SDK\Livewire\IpProfile;
use GhanaCompliance\Act843SDK\Livewire\DecisionEngine;
use GhanaCompliance\Act843SDK\Livewire\AttackGraph;
use GhanaCompliance\Act843SDK\Livewire\PredictedAttacks;

class ComplianceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register commands for both web and console
        $this->commands([
            \GhanaCompliance\Act843SDK\Console\Commands\ComplianceInstallCommand::class,
            \GhanaCompliance\Act843SDK\Console\Commands\ComplianceScanPasswords::class,
            \GhanaCompliance\Act843SDK\Console\Commands\ComplianceScanRetention::class,
            \GhanaCompliance\Act843SDK\Console\Commands\CompliancePurgeOldLogs::class,
            \GhanaCompliance\Act843SDK\Console\Commands\ComplianceWeeklyReport::class,
            \GhanaCompliance\Act843SDK\Console\Commands\ComplianceEvaluate::class,
            \GhanaCompliance\Act843SDK\Console\Commands\DecayIpReputations::class,
        ]);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/compliance.php' => config_path('compliance.php'),
            ], 'compliance-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/' => database_path('migrations'),
            ], 'compliance-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/compliance'),
            ], 'compliance-views');
        }

        // Register event listener for failed login attempts
        Event::listen(Failed::class, \GhanaCompliance\Act843SDK\Listeners\LogFailedLoginAttempt::class);

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Load views (namespace 'compliance::')
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'compliance');

        // Register Livewire components
        Livewire::component('security-dashboard', SecurityDashboard::class);
        Livewire::component('ip-profile', IpProfile::class);
        Livewire::component('decision-engine', DecisionEngine::class);
        Livewire::component('attack-graph', AttackGraph::class);
        Livewire::component('predicted-attacks', PredictedAttacks::class);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/compliance.php', 'compliance');
    }
}

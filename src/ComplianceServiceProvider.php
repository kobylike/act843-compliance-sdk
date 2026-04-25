<?php

namespace GhanaCompliance\Act843SDK;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Failed;
use GhanaCompliance\Act843SDK\Commands\ComplianceInstallCommand;

class ComplianceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
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

            $this->commands([
                ComplianceInstallCommand::class,
            ]);
        }

        Event::listen(Failed::class, \GhanaCompliance\Act843SDK\Listeners\LogFailedLoginAttempt::class);

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'compliance');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/compliance.php', 'compliance');
    }
}

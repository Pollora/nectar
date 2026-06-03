<?php

declare(strict_types=1);

namespace Pollora\Nectar;

use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Pollora\Nectar\Console\StartCommand;
use Pollora\Nectar\Mcp\Nectar;

class NectarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nectar.php', 'nectar');

        if (! $this->isEnabled()) {
            return;
        }

        $this->app->singleton(Nectar::class);
    }

    public function boot(): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->registerPublishing();
        $this->registerCommands();
        $this->registerMcpServer();
    }

    private function isEnabled(): bool
    {
        return (bool) config('nectar.enabled', true)
            && $this->app->environment('local', 'development');
    }

    private function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/nectar.php' => config_path('nectar.php'),
            ], 'nectar-config');
        }
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                StartCommand::class,
            ]);
        }
    }

    private function registerMcpServer(): void
    {
        Mcp::local('pollora-nectar', Nectar::class);
    }
}

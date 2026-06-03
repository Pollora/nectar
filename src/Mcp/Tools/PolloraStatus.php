<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Tools;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Pollora\Discovery\Application\Services\DiscoveryManager;

#[IsReadOnly]
class PolloraStatus extends Tool
{
    protected string $name = 'pollora_status';

    protected string $description = 'Get overall Pollora framework status: PHP/Laravel/WordPress versions, active theme, discovery cache status, and installed Pollora packages.';

    public function handle(Request $request): Response
    {
        $polloraVersion = $this->getPolloraVersion();
        $wpVersion = defined('ABSPATH') && function_exists('get_bloginfo')
            ? get_bloginfo('version')
            : 'not loaded';

        $activeTheme = function_exists('get_stylesheet')
            ? get_stylesheet()
            : 'unknown';

        $discoveryCached = $this->isDiscoveryCached();

        $polloraPackages = collect(json_decode(
            File::get(base_path('composer.lock')),
            true
        )['packages'] ?? [])->filter(
            fn (array $pkg): bool => str_starts_with($pkg['name'], 'pollora/')
        )->map(fn (array $pkg): array => [
            'name' => $pkg['name'],
            'version' => $pkg['version'],
        ])->values()->all();

        return Response::json([
            'php_version' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION,
            'laravel_version' => app()->version(),
            'pollora_version' => $polloraVersion,
            'wordpress_version' => $wpVersion,
            'environment' => app()->environment(),
            'active_theme' => $activeTheme,
            'discovery_cached' => $discoveryCached,
            'pollora_packages' => $polloraPackages,
        ]);
    }

    private function isDiscoveryCached(): bool
    {
        if (! app()->bound(DiscoveryManager::class)) {
            return false;
        }

        // Check if any discovery cache keys exist in Laravel cache
        return Cache::has('pollora-discoverer-cache-discovery');
    }

    private function getPolloraVersion(): string
    {
        $composerLock = json_decode(File::get(base_path('composer.lock')), true);

        foreach ($composerLock['packages'] ?? [] as $package) {
            if ($package['name'] === 'pollora/framework') {
                return $package['version'];
            }
        }

        return 'unknown';
    }
}

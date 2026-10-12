<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Tools;

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Pollora\Discovery\Application\Services\DiscoveryManager;
use Pollora\Discovery\Infrastructure\Services\DiscoveryCacheManager;

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

    /**
     * Whether discovery reads its scans from the persistent cache, and that
     * cache holds every class on disk.
     *
     * False in debug mode (the cache is off), before the first request has
     * filled it, and once a class added since is missing from it: the case
     * `php artisan discovery:clear` fixes.
     */
    private function isDiscoveryCached(): bool
    {
        if (! app()->bound(DiscoveryManager::class) || ! class_exists(DiscoveryCacheManager::class)) {
            return false;
        }

        $cache = app(DiscoveryCacheManager::class);

        try {
            if (! $cache->isCacheEnabled()) {
                return false;
            }

            $locations = app(DiscoveryManager::class)->getLocations();

            if ($locations->isEmpty()) {
                return false;
            }

            foreach ($locations as $location) {
                // null: this location was never cached; a list: classes the cache lacks.
                if ($cache->classesMissingFromCache($location) !== []) {
                    return false;
                }
            }
        } catch (\Error) {
            // Frameworks before 13.34 have neither method, and cannot tell.
            return false;
        }

        return true;
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

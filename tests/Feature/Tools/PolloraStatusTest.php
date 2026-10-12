<?php

declare(strict_types=1);

use Pollora\Discovery\Application\Services\DiscoveryManager;
use Pollora\Discovery\Domain\Contracts\DiscoveryLocationInterface;
use Pollora\Discovery\Infrastructure\Services\DiscoveryCacheManager;
use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\Mcp\Tools\PolloraStatus;

beforeEach(function (): void {
    $composerLock = json_encode([
        'packages' => [
            ['name' => 'pollora/framework', 'version' => 'v13.4.0'],
            ['name' => 'pollora/nectar', 'version' => 'v0.1.0'],
            ['name' => 'laravel/framework', 'version' => 'v13.0.0'],
        ],
    ]);

    file_put_contents(base_path('composer.lock'), $composerLock);
});

afterEach(function (): void {
    @unlink(base_path('composer.lock'));
    @unlink(base_path('bootstrap/cache/discovery.php'));
    @rmdir(base_path('bootstrap/cache'));
    @rmdir(base_path('bootstrap'));
});

it('returns framework status with all expected keys', function (): void {
    @mkdir(base_path('bootstrap/cache'), 0777, true);
    file_put_contents(base_path('bootstrap/cache/discovery.php'), '<?php return [];');

    Nectar::tool(PolloraStatus::class)
        ->assertOk()
        ->assertSee('v13.4.0')
        ->assertSee('6.9.0');
});

it('reports pollora packages from composer.lock', function (): void {
    Nectar::tool(PolloraStatus::class)
        ->assertOk()
        ->assertSee('pollora/framework')
        ->assertSee('pollora/nectar')
        ->assertDontSee('"laravel/framework"');
});

/**
 * Bind a discovery manager over one location, and a cache manager that
 * answers for it.
 *
 * @param  array<int, string>|null  $missing  Classes the cache lacks; null when the location was never cached
 */
function discoveryCacheReporting(bool $enabled, ?array $missing): void
{
    $location = Mockery::mock(DiscoveryLocationInterface::class);

    $manager = Mockery::mock(DiscoveryManager::class);
    $manager->shouldReceive('getLocations')->andReturn(collect([$location]));
    app()->instance(DiscoveryManager::class, $manager);

    $cache = Mockery::mock(DiscoveryCacheManager::class);
    $cache->shouldReceive('isCacheEnabled')->andReturn($enabled);
    $cache->shouldReceive('classesMissingFromCache')->with($location)->andReturn($missing);
    app()->instance(DiscoveryCacheManager::class, $cache);
}

it('reports discovery as cached when the cache holds every class on disk', function (): void {
    discoveryCacheReporting(enabled: true, missing: []);

    Nectar::tool(PolloraStatus::class)
        ->assertOk()
        ->assertSee('"discovery_cached":true');
});

it('reports discovery as not cached when a class on disk is missing from the cache', function (): void {
    discoveryCacheReporting(enabled: true, missing: ['App\\Hooks\\NewAction']);

    Nectar::tool(PolloraStatus::class)
        ->assertOk()
        ->assertSee('"discovery_cached":false');
});

it('reports discovery as not cached when a location was never cached', function (): void {
    discoveryCacheReporting(enabled: true, missing: null);

    Nectar::tool(PolloraStatus::class)
        ->assertOk()
        ->assertSee('"discovery_cached":false');
});

it('reports discovery as not cached when the cache is off', function (): void {
    discoveryCacheReporting(enabled: false, missing: []);

    Nectar::tool(PolloraStatus::class)
        ->assertOk()
        ->assertSee('"discovery_cached":false');
});

it('reports discovery as not cached without the framework', function (): void {
    app()->offsetUnset(DiscoveryManager::class);

    Nectar::tool(PolloraStatus::class)
        ->assertOk()
        ->assertSee('"discovery_cached":false');
});

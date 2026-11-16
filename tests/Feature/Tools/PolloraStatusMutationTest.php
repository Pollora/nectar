<?php

declare(strict_types=1);

use Laravel\Mcp\Request;
use Pollora\Discovery\Application\Services\DiscoveryManager;
use Pollora\Discovery\Infrastructure\Services\DiscoveryCacheManager;
use Pollora\Nectar\Mcp\Tools\PolloraStatus;

/**
 * @return array<string, mixed>
 */
function polloraStatusMutationPayload(): array
{
    return json_decode((string) (new PolloraStatus)->handle(new Request([]))->content(), true);
}

beforeEach(function (): void {
    file_put_contents(base_path('composer.lock'), json_encode([
        'packages' => [
            ['name' => 'laravel/framework', 'version' => 'v13.0.0'],
            ['name' => 'pollora/nectar', 'version' => 'v0.1.0'],
            ['name' => 'pollora/framework', 'version' => 'v13.4.0'],
            ['name' => 'pollora/discovery', 'version' => 'v0.2.1'],
        ],
    ]));
});

afterEach(function (): void {
    @unlink(base_path('composer.lock'));
    app()->forgetInstance(DiscoveryManager::class);
    app()->forgetInstance(DiscoveryCacheManager::class);
    app()->offsetUnset(DiscoveryManager::class);
});

it('reports every key with its value', function (): void {
    $payload = polloraStatusMutationPayload();

    expect($payload)->toBe([
        'php_version' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION,
        'laravel_version' => app()->version(),
        'pollora_version' => 'v13.4.0',
        'wordpress_version' => '6.9.0',
        'environment' => app()->environment(),
        'active_theme' => 'test-theme',
        'discovery_cached' => $payload['discovery_cached'],
        'pollora_packages' => [
            ['name' => 'pollora/nectar', 'version' => 'v0.1.0'],
            ['name' => 'pollora/framework', 'version' => 'v13.4.0'],
            ['name' => 'pollora/discovery', 'version' => 'v0.2.1'],
        ],
    ]);
});

it('writes the PHP version as three numbers separated by dots', function (): void {
    expect(polloraStatusMutationPayload()['php_version'])->toMatch('/^\d+\.\d+\.\d+$/');
});

it('does not read the discovery cache when the discovery manager is not bound', function (): void {
    app()->offsetUnset(DiscoveryManager::class);

    $cache = Mockery::mock(DiscoveryCacheManager::class);
    $cache->shouldNotReceive('isCacheEnabled');
    $cache->shouldNotReceive('classesMissingFromCache');
    app()->instance(DiscoveryCacheManager::class, $cache);

    expect(polloraStatusMutationPayload()['discovery_cached'])->toBeFalse();
});

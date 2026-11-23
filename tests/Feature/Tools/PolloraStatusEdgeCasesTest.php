<?php

declare(strict_types=1);

use Laravel\Mcp\Request;
use Pollora\Discovery\Application\Services\DiscoveryManager;
use Pollora\Discovery\Infrastructure\Services\DiscoveryCacheManager;
use Pollora\Nectar\Mcp\Tools\PolloraStatus;
use Pollora\Nectar\Tests\Helpers\FakeDiscovery;

/**
 * @return array<string, mixed>
 */
function polloraStatusPayload(): array
{
    return json_decode((string) (new PolloraStatus)->handle(new Request([]))->content(), true);
}

beforeEach(function (): void {
    file_put_contents(base_path('composer.lock'), json_encode([
        'packages' => [['name' => 'laravel/framework', 'version' => 'v13.0.0']],
    ]));
});

afterEach(function (): void {
    @unlink(base_path('composer.lock'));
    app()->forgetInstance(DiscoveryManager::class);
    app()->forgetInstance(DiscoveryCacheManager::class);
});

it('reports an unknown framework version when composer.lock does not list the framework', function (): void {
    $payload = polloraStatusPayload();

    expect($payload['pollora_version'])->toBe('unknown')
        ->and($payload['pollora_packages'])->toBe([]);
});

it('reports discovery as not cached when there is no location to cache', function (): void {
    app()->instance(DiscoveryManager::class, FakeDiscovery::manager());

    $cache = Mockery::mock(DiscoveryCacheManager::class);
    $cache->shouldReceive('isCacheEnabled')->andReturn(true);
    app()->instance(DiscoveryCacheManager::class, $cache);

    expect(polloraStatusPayload()['discovery_cached'])->toBeFalse();
});

it('reports discovery as not cached when the framework predates the cache checks', function (): void {
    app()->instance(DiscoveryManager::class, FakeDiscovery::manager());

    $cache = Mockery::mock(DiscoveryCacheManager::class);
    $cache->shouldReceive('isCacheEnabled')->andThrow(new Error('Call to undefined method'));
    app()->instance(DiscoveryCacheManager::class, $cache);

    expect(polloraStatusPayload()['discovery_cached'])->toBeFalse();
});

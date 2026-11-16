<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\NectarServiceProvider;

it('publishes its configuration file under the nectar-config tag', function (): void {
    $paths = ServiceProvider::pathsToPublish(NectarServiceProvider::class, 'nectar-config');

    expect($paths)->toHaveCount(1)
        ->and(realpath((string) array_key_first($paths)))->toBe(realpath(__DIR__.'/../../config/nectar.php'))
        ->and(array_values($paths))->toBe([config_path('nectar.php')]);
});

it('registers the local MCP server under its handle', function (): void {
    expect(Mcp::getLocalServer('pollora-nectar'))->not->toBeNull();
});

it('is enabled by default when the configuration does not say', function (): void {
    $app = new Application(base_path());
    $app->instance('config', new Repository(['nectar' => []]));
    $app['env'] = 'local';

    (new NectarServiceProvider($app))->register();

    expect($app->bound(Nectar::class))->toBeTrue();
});

it('is enabled in the local environment too', function (): void {
    $app = new Application(base_path());
    $app->instance('config', new Repository(['nectar' => ['enabled' => true]]));
    $app['env'] = 'local';

    (new NectarServiceProvider($app))->register();

    expect($app->bound(Nectar::class))->toBeTrue();
});

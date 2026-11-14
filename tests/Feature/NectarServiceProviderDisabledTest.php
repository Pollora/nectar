<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\NectarServiceProvider;

/**
 * A bare application, so the provider's own registration can be observed.
 */
function bareNectarApplication(bool $enabled, string $environment): Application
{
    $app = new Application(base_path());
    $app->instance('config', new Repository(['nectar' => ['enabled' => $enabled]]));
    $app['env'] = $environment;

    return $app;
}

it('registers nothing when nectar is switched off', function (): void {
    $app = bareNectarApplication(enabled: false, environment: 'local');
    $provider = new NectarServiceProvider($app);

    $provider->register();
    $provider->boot();

    expect($app->bound(Nectar::class))->toBeFalse();
});

it('registers nothing outside the local and development environments', function (): void {
    $app = bareNectarApplication(enabled: true, environment: 'production');
    $provider = new NectarServiceProvider($app);

    $provider->register();
    $provider->boot();

    expect($app->bound(Nectar::class))->toBeFalse();
});

it('registers the server as a singleton in the development environment', function (): void {
    $app = bareNectarApplication(enabled: true, environment: 'development');

    (new NectarServiceProvider($app))->register();

    expect($app->bound(Nectar::class))->toBeTrue()
        ->and($app->isShared(Nectar::class))->toBeTrue();
});

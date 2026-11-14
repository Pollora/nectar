<?php

declare(strict_types=1);

use Laravel\Mcp\Request;
use Pollora\Discovery\Application\Services\DiscoveryManager;
use Pollora\Nectar\Mcp\Tools\DiscoveredComponents;
use Pollora\Nectar\Mcp\Tools\RegisteredHooks;
use Pollora\Nectar\Mcp\Tools\WordPressInfo;
use Pollora\Nectar\Mcp\Tools\WordPressRoutes;
use Pollora\Nectar\Mcp\Tools\WpOption;
use Pollora\Route\Infrastructure\Models\Route as PolloraRoute;

/**
 * @param  array<string, mixed>  $arguments
 */
function toolPayload(object $tool, array $arguments = []): mixed
{
    $content = (string) $tool->handle(new Request($arguments))->content();

    return json_decode($content, true) ?? $content;
}

/**
 * A discovered item whose attribute instantiates to the given object.
 *
 * @param  array<string, mixed>  $item
 * @return array<string, mixed>
 */
function discoveredItem(object $attribute, array $item = []): array
{
    $reflection = Mockery::mock();
    $reflection->shouldReceive('newInstance')->andReturn($attribute);

    return ['class' => 'App\\Thing', 'method' => 'run', 'attribute' => $reflection, ...$item];
}

/**
 * An item whose attribute cannot be instantiated.
 *
 * @return array<string, mixed>
 */
function brokenDiscoveredItem(array $item = []): array
{
    $reflection = Mockery::mock();
    $reflection->shouldReceive('newInstance')->andThrow(new RuntimeException('bad attribute'));

    return ['class' => 'App\\Broken', 'method' => 'run', 'attribute' => $reflection, ...$item];
}

function discoveryReturning(array $byId): void
{
    $manager = Mockery::mock(DiscoveryManager::class);
    $manager->shouldReceive('getDiscoveredItems')->andReturnUsing(
        fn (string $id) => $byId[$id] instanceof Throwable ? throw $byId[$id] : $byId[$id]
    );
    app()->instance(DiscoveryManager::class, $manager);
}

afterEach(function (): void {
    app()->forgetInstance(DiscoveryManager::class);
});

it('asks for the discovery cache when hooks cannot be read', function (): void {
    discoveryReturning(['hooks' => new RuntimeException('no cache')]);

    expect(toolPayload(new RegisteredHooks))->toContain('discovery:run');
});

it('lists a hook whose attribute cannot be instantiated with default values', function (): void {
    discoveryReturning(['hooks' => [brokenDiscoveredItem(['type' => 'action'])]]);

    $hooks = toolPayload(new RegisteredHooks);

    expect($hooks['actions'])->toBe([
        ['class' => 'App\\Broken', 'method' => 'run', 'hook' => 'unknown', 'priority' => 10],
    ])->and($hooks['filters'])->toBe([]);
});

it('defaults the priority of a hook that declares none', function (): void {
    discoveryReturning(['hooks' => [discoveredItem((object) ['hook' => 'init', 'priority' => null], ['type' => 'filter'])]]);

    expect(toolPayload(new RegisteredHooks, ['type' => 'filter'])['filters'][0]['priority'])->toBe(10);
});

it('reports an empty list for a component type that discovery cannot read', function (): void {
    discoveryReturning(['hooks' => new RuntimeException('no cache')]);

    $payload = toolPayload(new DiscoveredComponents, ['type' => 'hook']);

    expect($payload['components'])->toBe(['hook' => []])
        ->and($payload['summary'])->toBe(['hook' => 0]);
});

it('lists a component whose attribute cannot be instantiated without details', function (): void {
    discoveryReturning(['post_types' => [brokenDiscoveredItem()]]);

    $payload = toolPayload(new DiscoveredComponents, ['type' => 'post_type']);

    expect($payload['components']['post_type'])->toBe([
        ['class' => 'App\\Broken', 'method' => 'run', 'details' => []],
    ]);
});

enum NectarTestRecurrence: string
{
    case Hourly = 'hourly';
}

it('formats a schedule recurrence whatever shape the attribute carries', function (mixed $recurrence, string $expected): void {
    discoveryReturning(['schedules' => [discoveredItem((object) ['hook' => 'job', 'recurrence' => $recurrence])]]);

    $payload = toolPayload(new DiscoveredComponents, ['type' => 'schedule']);

    expect($payload['components']['schedule'][0]['details'])->toBe(['hook' => 'job', 'recurrence' => $expected]);
})->with([
    'string' => ['daily', 'daily'],
    'array with a display name' => [['display' => 'Every ten minutes', 'interval' => 600], 'Every ten minutes'],
    'array without a display name' => [['interval' => 600], '{"interval":600}'],
    'backed enum' => [NectarTestRecurrence::Hourly, 'hourly'],
    'integer' => [3600, '3600'],
    'nothing' => [null, ''],
]);

it('rejects an empty option key', function (): void {
    expect(toolPayload(new WpOption, ['key' => '']))->toBe('The "key" parameter is required.');
});

it('reports the WordPress constants that are defined', function (): void {
    if (! defined('AUTOSAVE_INTERVAL')) {
        define('AUTOSAVE_INTERVAL', 120);
    }

    expect(toolPayload(new WordPressInfo)['constants'])->toMatchArray(['AUTOSAVE_INTERVAL' => AUTOSAVE_INTERVAL]);
});

it('gives a WordPress route its condition and the controller of its action', function (): void {
    $route = (new PolloraRoute(['GET'], 'wp-condition-page', ['uses' => fn () => 'ok', 'controller' => 'App\\Http\\PageController@show']))
        ->setIsWordPressRoute(true)
        ->setCondition('is_page')
        ->setConditionParameters(['about']);
    app('router')->getRoutes()->add($route);

    $payload = toolPayload(new WordPressRoutes);

    $listed = collect($payload['routes'])->firstWhere('uri', 'wp-condition-page');

    expect($listed)->toMatchArray([
        'is_wordpress_route' => true,
        'controller' => 'App\\Http\\PageController@show',
        'wp_condition' => 'is_page',
        'wp_condition_params' => ['about'],
    ])->and($payload['summary']['wordpress_routes'])->toBe(1);
})->skip(
    ! class_exists(PolloraRoute::class),
    'The route model of the framework this test builds is not in the oldest framework the package accepts.'
);

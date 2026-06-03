<?php

declare(strict_types=1);

use Pollora\Discovery\Application\Services\DiscoveryManager;
use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\Mcp\Tools\RegisteredHooks;

beforeEach(function () {
    $actionAttribute = Mockery::mock();
    $actionAttribute->shouldReceive('newInstance')->andReturn((object) [
        'hook' => 'init',
        'priority' => 20,
    ]);

    $enqueueAttribute = Mockery::mock();
    $enqueueAttribute->shouldReceive('newInstance')->andReturn((object) [
        'hook' => 'wp_enqueue_scripts',
        'priority' => 10,
    ]);

    $filterAttribute = Mockery::mock();
    $filterAttribute->shouldReceive('newInstance')->andReturn((object) [
        'hook' => 'the_content',
        'priority' => 10,
    ]);

    $titleFilterAttribute = Mockery::mock();
    $titleFilterAttribute->shouldReceive('newInstance')->andReturn((object) [
        'hook' => 'the_title',
        'priority' => 10,
    ]);

    $discoveredItems = [
        [
            'type' => 'action',
            'class' => 'App\\Hooks\\ThemeHooks',
            'method' => 'onInit',
            'attribute' => $actionAttribute,
        ],
        [
            'type' => 'action',
            'class' => 'App\\Hooks\\ThemeHooks',
            'method' => 'enqueueAssets',
            'attribute' => $enqueueAttribute,
        ],
        [
            'type' => 'filter',
            'class' => 'App\\Hooks\\ThemeHooks',
            'method' => 'filterContent',
            'attribute' => $filterAttribute,
        ],
        [
            'type' => 'filter',
            'class' => 'App\\Hooks\\AdminHooks',
            'method' => 'filterTitle',
            'attribute' => $titleFilterAttribute,
        ],
    ];

    $discoveryManager = Mockery::mock(DiscoveryManager::class);
    $discoveryManager->shouldReceive('getDiscoveredItems')
        ->with('hooks')
        ->andReturn($discoveredItems);

    app()->instance(DiscoveryManager::class, $discoveryManager);
});

it('returns both actions and filters when no type filter', function () {
    Nectar::tool(RegisteredHooks::class)
        ->assertOk()
        ->assertSee('init')
        ->assertSee('wp_enqueue_scripts')
        ->assertSee('the_content')
        ->assertSee('the_title');
});

it('filters by action type', function () {
    Nectar::tool(RegisteredHooks::class, ['type' => 'action'])
        ->assertOk()
        ->assertSee('init')
        ->assertSee('wp_enqueue_scripts')
        ->assertDontSee('the_content');
});

it('filters by filter type', function () {
    Nectar::tool(RegisteredHooks::class, ['type' => 'filter'])
        ->assertOk()
        ->assertSee('the_content')
        ->assertSee('the_title')
        ->assertDontSee('init');
});

it('includes class and method information', function () {
    Nectar::tool(RegisteredHooks::class, ['type' => 'action'])
        ->assertOk()
        ->assertSee('ThemeHooks')
        ->assertSee('onInit');
});

it('includes priority', function () {
    Nectar::tool(RegisteredHooks::class, ['type' => 'action'])
        ->assertOk()
        ->assertSee('20');
});

it('reports missing discovery system', function () {
    app()->forgetInstance(DiscoveryManager::class);

    Nectar::tool(RegisteredHooks::class)
        ->assertOk()
        ->assertSee('Discovery system is not available');
});

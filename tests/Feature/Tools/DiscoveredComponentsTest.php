<?php

declare(strict_types=1);

use Pollora\Discovery\Application\Services\DiscoveryManager;
use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\Mcp\Tools\DiscoveredComponents;
use Pollora\Nectar\Tests\Helpers\FakeDiscovery;
use Pollora\Schedule\Interval;

beforeEach(function () {
    $postTypeAttr = Mockery::mock();
    $postTypeAttr->shouldReceive('newInstance')->andReturn((object) [
        'slug' => 'book',
        'singular' => 'Book',
        'plural' => 'Books',
    ]);

    $taxonomyAttr = Mockery::mock();
    $taxonomyAttr->shouldReceive('newInstance')->andReturn((object) [
        'slug' => 'genre',
        'singular' => 'Genre',
        'objectType' => 'book',
    ]);

    $actionAttr = Mockery::mock();
    $actionAttr->shouldReceive('newInstance')->andReturn((object) [
        'hook' => 'init',
        'priority' => 10,
    ]);

    $filterAttr = Mockery::mock();
    $filterAttr->shouldReceive('newInstance')->andReturn((object) [
        'hook' => 'the_content',
        'priority' => 10,
    ]);

    $scheduleAttr = Mockery::mock();
    $scheduleAttr->shouldReceive('newInstance')->andReturn((object) [
        'hook' => 'cleanup_hook',
        'recurrence' => 'daily',
    ]);

    $restRouteAttr = Mockery::mock();
    $restRouteAttr->shouldReceive('newInstance')->andReturn((object) [
        'namespace' => 'app/v1',
        'route' => '/items',
        'permissionCallback' => null,
    ]);

    app()->instance(DiscoveryManager::class, FakeDiscovery::manager([
        'post_types' => [
            ['class' => 'App\\PostTypes\\Book', 'method' => null, 'attribute' => $postTypeAttr],
        ],
        'taxonomies' => [
            ['class' => 'App\\Taxonomies\\Genre', 'method' => null, 'attribute' => $taxonomyAttr],
        ],
        'hooks' => [
            ['type' => 'action', 'class' => 'App\\Hooks\\ThemeHooks', 'method' => 'onInit', 'attribute' => $actionAttr],
            ['type' => 'filter', 'class' => 'App\\Hooks\\ThemeHooks', 'method' => 'filterContent', 'attribute' => $filterAttr],
        ],
        'schedules' => [
            ['class' => 'App\\Schedule\\CleanupTask', 'method' => 'handle', 'attribute' => $scheduleAttr],
        ],
        'wp_rest_routes' => [
            ['class' => 'App\\Api\\ItemEndpoint', 'method' => null, 'attribute' => $restRouteAttr],
        ],
    ]));
});

it('returns all component types with summary', function () {
    Nectar::tool(DiscoveredComponents::class)
        ->assertOk()
        ->assertSee('post_type')
        ->assertSee('taxonomy')
        ->assertSee('hook')
        ->assertSee('schedule')
        ->assertSee('rest_route');
});

it('filters by post_type', function () {
    Nectar::tool(DiscoveredComponents::class, ['type' => 'post_type'])
        ->assertOk()
        ->assertSee('Book')
        ->assertDontSee('Genre');
});

it('filters by taxonomy', function () {
    Nectar::tool(DiscoveredComponents::class, ['type' => 'taxonomy'])
        ->assertOk()
        ->assertSee('Genre')
        ->assertDontSee('Book');
});

it('filters by schedule', function () {
    Nectar::tool(DiscoveredComponents::class, ['type' => 'schedule'])
        ->assertOk()
        ->assertSee('CleanupTask')
        ->assertSee('daily');
});

it('filters by rest_route', function () {
    Nectar::tool(DiscoveredComponents::class, ['type' => 'rest_route'])
        ->assertOk()
        ->assertSee('ItemEndpoint')
        ->assertSee('app/v1');
});

it('rejects invalid type', function () {
    Nectar::tool(DiscoveredComponents::class, ['type' => 'invalid'])
        ->assertOk()
        ->assertSee('Invalid type');
});

it('reports missing discovery system', function () {
    app()->forgetInstance(DiscoveryManager::class);

    Nectar::tool(DiscoveredComponents::class)
        ->assertOk()
        ->assertSee('Discovery system is not available');
});

it('names a schedule that recurs at a custom interval', function () {
    $attribute = Mockery::mock();
    $attribute->shouldReceive('newInstance')->andReturn((object) [
        'hook' => 'sync_hook',
        'recurrence' => new Interval(minutes: 5, display: 'Every five minutes'),
    ]);
    app()->instance(DiscoveryManager::class, FakeDiscovery::manager([
        'schedules' => [['class' => 'App\\Schedule\\SyncTask', 'method' => 'handle', 'attribute' => $attribute]],
    ]));

    Nectar::tool(DiscoveredComponents::class, ['type' => 'schedule'])
        ->assertOk()
        ->assertSee('sync_hook')
        ->assertSee('Every five minutes');
});

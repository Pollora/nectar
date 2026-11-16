<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Pollora\Discovery\Application\Services\DiscoveryManager;
use Pollora\Nectar\Mcp\Tools\ModulesInfo;
use Pollora\Nectar\Mcp\Tools\PostTypesInfo;
use Pollora\Nectar\Mcp\Tools\RegisteredHooks;
use Pollora\Nectar\Mcp\Tools\TaxonomiesInfo;
use Pollora\Nectar\Mcp\Tools\WordPressRoutes;
use Pollora\Nectar\Mcp\Tools\WpOption;
use Pollora\Route\Infrastructure\Models\Route as PolloraRoute;

/**
 * @param  array<string, mixed>  $arguments
 */
function toolMutationDecode(object $tool, array $arguments = []): mixed
{
    $content = (string) $tool->handle(new Request($arguments))->content();

    return json_decode($content, true) ?? $content;
}

beforeEach(function (): void {
    $GLOBALS['nectar_test_options'] = [];
    $GLOBALS['nectar_test_missing_options'] = [];
});

afterEach(function (): void {
    unset($GLOBALS['nectar_test_options'], $GLOBALS['nectar_test_missing_options']);
    app()->forgetInstance('modules');
});

it('describes the custom post types field by field', function (): void {
    $payload = toolMutationDecode(new PostTypesInfo);

    expect(array_keys($payload))->toBe(['book', 'product'])
        ->and($payload['book'])->toBe([
            'label' => 'Books',
            'singular' => 'Book',
            'public' => true,
            'publicly_queryable' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'hierarchical' => false,
            'supports' => ['title' => true, 'editor' => true, 'thumbnail' => true],
            'taxonomies' => ['genre'],
            'rewrite' => ['slug' => 'books'],
            'menu_icon' => 'dashicons-book',
            'capability_type' => 'post',
        ]);
});

it('lists the built-in post types too on request and filters by part of the slug', function (): void {
    expect(array_keys(toolMutationDecode(new PostTypesInfo, ['include_builtin' => true])))->toBe(['post', 'book', 'product'])
        ->and(array_keys(toolMutationDecode(new PostTypesInfo, ['include_builtin' => true, 'filter' => 'oo'])))->toBe(['book']);
});

it('describes the custom taxonomies field by field', function (): void {
    $payload = toolMutationDecode(new TaxonomiesInfo);

    expect($payload)->toBe([
        'genre' => [
            'label' => 'Genres',
            'singular' => 'Genre',
            'object_types' => ['book'],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'genre'],
        ],
    ]);
});

it('filters the taxonomies by part of the slug, built-in ones included on request', function (): void {
    expect(array_keys(toolMutationDecode(new TaxonomiesInfo, ['include_builtin' => true])))->toBe(['category', 'genre'])
        ->and(array_keys(toolMutationDecode(new TaxonomiesInfo, ['include_builtin' => true, 'filter' => 'enr'])))->toBe(['genre']);
});

it('lists the modules with their relative path and state', function (): void {
    $blog = Mockery::mock();
    $blog->shouldReceive('getName')->andReturn('Blog');
    $blog->shouldReceive('getPath')->andReturn(base_path('Modules/Blog'));
    $blog->shouldReceive('isEnabled')->andReturn(true);

    $shop = Mockery::mock();
    $shop->shouldReceive('getName')->andReturn('Shop');
    $shop->shouldReceive('getPath')->andReturn(base_path('Modules/Shop'));
    $shop->shouldReceive('isEnabled')->andReturn(false);

    $manager = Mockery::mock();
    $manager->shouldReceive('all')->andReturn([$blog, $shop]);
    app()->instance('modules', $manager);

    expect(toolMutationDecode(new ModulesInfo))->toBe([
        'available' => true,
        'count' => 2,
        'modules' => [
            'Blog' => ['name' => 'Blog', 'path' => 'Modules/Blog', 'enabled' => true],
            'Shop' => ['name' => 'Shop', 'path' => 'Modules/Shop', 'enabled' => false],
        ],
    ]);
});

it('says exactly why the modules are unavailable', function (): void {
    expect(toolMutationDecode(new ModulesInfo))->toBe([
        'available' => false,
        'message' => 'Laravel Modules (nwidart) is not installed or registered.',
    ]);
});

it('reads an option with its key and value', function (mixed $stored): void {
    $GLOBALS['nectar_test_options'] = ['my_option' => $stored];

    expect(toolMutationDecode(new WpOption, ['key' => 'my_option']))->toBe(['key' => 'my_option', 'exists' => true, 'value' => $stored]);
})->with(['text' => ['text'], 'false' => [false], 'zero' => [0], 'empty string' => [''], 'list' => [[1, 2]]]);

it('says an option does not exist, with its key', function (): void {
    $GLOBALS['nectar_test_missing_options'] = ['absent_option'];

    expect(toolMutationDecode(new WpOption, ['key' => 'absent_option']))->toBe(['key' => 'absent_option', 'exists' => false, 'value' => null]);
});

it('lists a Laravel route with its name and middleware, and counts it', function (): void {
    Route::get('/mutation-probe', fn () => 'ok')->name('mutation.probe')->middleware('throttle:60,1');

    $payload = toolMutationDecode(new WordPressRoutes);
    $route = collect($payload['routes'])->firstWhere('uri', 'mutation-probe');

    expect($route)->toBe([
        'uri' => 'mutation-probe',
        'methods' => ['GET', 'HEAD'],
        'name' => 'mutation.probe',
        'middleware' => ['throttle:60,1'],
        'is_wordpress_route' => false,
    ])->and($payload['summary'])->toBe([
        'total' => count($payload['routes']),
        'wordpress_routes' => 0,
        'laravel_routes' => count($payload['routes']),
    ]);
});

it('counts the WordPress routes apart from the Laravel ones and gives a condition only to the former', function (): void {
    $wordpress = (new PolloraRoute(['GET'], 'wp-page', ['uses' => fn () => 'ok']))
        ->setIsWordPressRoute(true)
        ->setCondition('is_page');
    $plain = (new PolloraRoute(['GET'], 'plain-page', ['uses' => fn () => 'ok']))
        ->setIsWordPressRoute(false)
        ->setCondition('is_single');
    app('router')->getRoutes()->add($wordpress);
    app('router')->getRoutes()->add($plain);

    $payload = toolMutationDecode(new WordPressRoutes);
    $routes = collect($payload['routes'])->keyBy('uri');

    expect($routes['wp-page'])->toHaveKey('wp_condition')
        ->and($routes['plain-page'])->not->toHaveKey('wp_condition')
        ->and($routes['plain-page'])->not->toHaveKey('wp_condition_params')
        ->and($payload['summary']['wordpress_routes'])->toBe(1)
        ->and($payload['summary']['laravel_routes'])->toBe(count($payload['routes']) - 1);
})->skip(
    ! class_exists(PolloraRoute::class),
    'The route model of the framework this test builds is not in the oldest framework the package accepts.'
);

it('lists both kinds of hook when no type is asked, and only the asked kind otherwise', function (?string $type, array $expected): void {
    $manager = Mockery::mock(DiscoveryManager::class);
    $manager->shouldReceive('getDiscoveredItems')->andReturn([]);
    app()->instance(DiscoveryManager::class, $manager);

    expect(toolMutationDecode(new RegisteredHooks, $type === null ? [] : ['type' => $type]))->toBe($expected);

    app()->forgetInstance(DiscoveryManager::class);
})->with([
    'both' => [null, ['actions' => [], 'filters' => []]],
    'actions' => ['action', ['actions' => []]],
    'filters' => ['filter', ['filters' => []]],
]);

<?php

declare(strict_types=1);

use Laravel\Mcp\Request;
use Pollora\Discovery\Application\Services\DiscoveryManager;
use Pollora\Nectar\Mcp\Tools\DiscoveredComponents;
use Pollora\Nectar\Tests\Helpers\FakeDiscovery;

/**
 * @param  array<string, mixed>  $arguments
 * @return array<string, mixed>|string
 */
function discoveredMutationResult(array $arguments = []): array|string
{
    $content = (string) (new DiscoveredComponents)->handle(new Request($arguments))->content();

    return json_decode($content, true) ?? $content;
}

/**
 * @param  array<string, list<array<string, mixed>>>  $byId
 */
function discoveredMutationManager(array $byId): void
{
    app()->instance(DiscoveryManager::class, FakeDiscovery::manager($byId));
}

/**
 * @param  array<string, mixed>  $item
 * @return array<string, mixed>
 */
function discoveredMutationItem(object $attribute, array $item = []): array
{
    $reflection = Mockery::mock();
    $reflection->shouldReceive('newInstance')->andReturn($attribute);

    return ['attribute' => $reflection, ...$item];
}

afterEach(function (): void {
    app()->forgetInstance(DiscoveryManager::class);
});

it('names the valid types in the message for an unknown one', function (): void {
    discoveredMutationManager([]);

    expect(discoveredMutationResult(['type' => 'widget']))->toBe('Invalid type. Valid types: post_type, taxonomy, hook, schedule, rest_route');
});

it('describes every kind of component and counts them', function (): void {
    discoveredMutationManager([
        'post_types' => [discoveredMutationItem((object) ['slug' => 'book', 'singular' => 'Book', 'plural' => 'Books'], ['class' => 'App\\Book', 'method' => null])],
        'taxonomies' => [discoveredMutationItem((object) ['slug' => 'genre', 'singular' => 'Genre', 'objectType' => 'book'], ['class' => 'App\\Genre', 'method' => null])],
        'hooks' => [
            discoveredMutationItem((object) ['hook' => 'init', 'priority' => 3], ['type' => 'action', 'class' => 'App\\Hooks', 'method' => 'boot']),
            discoveredMutationItem((object) ['hook' => 'the_content', 'priority' => 0], ['type' => 'filter', 'class' => 'App\\Hooks', 'method' => 'filter']),
        ],
        'schedules' => [discoveredMutationItem((object) ['hook' => 'tidy', 'recurrence' => 'daily'], ['class' => 'App\\Tidy', 'method' => 'handle'])],
        'wp_rest_routes' => [discoveredMutationItem((object) ['namespace' => 'app/v1', 'route' => '/items', 'permissionCallback' => 'can_read'], ['class' => 'App\\Items', 'method' => 'index'])],
    ]);

    expect(discoveredMutationResult())->toBe([
        'summary' => ['post_type' => 1, 'taxonomy' => 1, 'hook' => 2, 'schedule' => 1, 'rest_route' => 1],
        'components' => [
            'post_type' => [['class' => 'App\\Book', 'method' => null, 'details' => ['slug' => 'book', 'singular' => 'Book', 'plural' => 'Books']]],
            'taxonomy' => [['class' => 'App\\Genre', 'method' => null, 'details' => ['slug' => 'genre', 'singular' => 'Genre', 'object_type' => 'book']]],
            'hook' => [
                ['class' => 'App\\Hooks', 'method' => 'boot', 'details' => ['type' => 'action', 'hook' => 'init', 'priority' => 3]],
                ['class' => 'App\\Hooks', 'method' => 'filter', 'details' => ['type' => 'filter', 'hook' => 'the_content', 'priority' => 0]],
            ],
            'schedule' => [['class' => 'App\\Tidy', 'method' => 'handle', 'details' => ['hook' => 'tidy', 'recurrence' => 'daily']]],
            'rest_route' => [['class' => 'App\\Items', 'method' => 'index', 'details' => ['namespace' => 'app/v1', 'route' => '/items', 'permission_callback' => 'can_read']]],
        ],
    ]);
});

it('fills what an attribute leaves out with the documented defaults', function (string $id, string $type, array $expected): void {
    discoveredMutationManager([$id => [discoveredMutationItem((object) [])]]);

    $payload = discoveredMutationResult(['type' => $type]);

    expect($payload['components'][$type])->toBe([['class' => 'unknown', 'method' => null, 'details' => $expected]]);
})->with([
    'post type' => ['post_types', 'post_type', ['slug' => null, 'singular' => null, 'plural' => null]],
    'taxonomy' => ['taxonomies', 'taxonomy', ['slug' => null, 'singular' => null, 'object_type' => null]],
    'hook' => ['hooks', 'hook', ['type' => 'unknown', 'hook' => 'unknown', 'priority' => 10]],
    'schedule' => ['schedules', 'schedule', ['hook' => null, 'recurrence' => '']],
    'rest route' => ['wp_rest_routes', 'rest_route', ['namespace' => '', 'route' => '', 'permission_callback' => null]],
]);

it('summarises only the requested type', function (): void {
    discoveredMutationManager(['post_types' => [discoveredMutationItem((object) ['slug' => 'book'])]]);

    $payload = discoveredMutationResult(['type' => 'post_type']);

    expect(array_keys($payload['components']))->toBe(['post_type'])
        ->and($payload['summary'])->toBe(['post_type' => 1]);
});

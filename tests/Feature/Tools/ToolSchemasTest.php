<?php

declare(strict_types=1);

use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Pollora\Nectar\Mcp\Tools\DiscoveredComponents;
use Pollora\Nectar\Mcp\Tools\PostTypesInfo;
use Pollora\Nectar\Mcp\Tools\RegisteredHooks;
use Pollora\Nectar\Mcp\Tools\ScheduledEvents;
use Pollora\Nectar\Mcp\Tools\TaxonomiesInfo;
use Pollora\Nectar\Mcp\Tools\WpOption;

it('declares the arguments each tool accepts', function (string $tool, array $arguments): void {
    $schema = (new $tool)->schema(new JsonSchemaTypeFactory);

    expect(array_keys($schema))->toBe($arguments);
})->with([
    'post types' => [PostTypesInfo::class, ['filter', 'include_builtin']],
    'taxonomies' => [TaxonomiesInfo::class, ['filter', 'include_builtin']],
    'hooks' => [RegisteredHooks::class, ['type']],
    'components' => [DiscoveredComponents::class, ['type']],
    'scheduled events' => [ScheduledEvents::class, ['type', 'driver', 'limit']],
    'option' => [WpOption::class, ['key']],
]);

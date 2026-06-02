<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class DiscoveredComponents extends Tool
{
    protected string $name = 'discovered_components';

    protected string $description = 'List all auto-discovered Pollora components from the discovery cache, grouped by type: post types, taxonomies, hooks (actions/filters), schedules, and REST routes.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->description('Filter by component type: "post_type", "taxonomy", "action", "filter", "schedule", "rest_route". Omit for all.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $type = $request->get('type');

        $cachePath = base_path('bootstrap/cache/discovery.php');

        if (! File::exists($cachePath)) {
            return Response::text('Discovery cache not found. Run "php artisan discovery:run" to generate it.');
        }

        $discovered = require $cachePath;

        $attributeMap = [
            'post_type' => 'Pollora\Attributes\PostType\PostType',
            'taxonomy' => 'Pollora\Attributes\Taxonomy\Taxonomy',
            'action' => 'Pollora\Attributes\Action',
            'filter' => 'Pollora\Attributes\Filter',
            'schedule' => 'Pollora\Attributes\Schedule',
            'rest_route' => 'Pollora\Attributes\WpRestRoute',
        ];

        if ($type && ! isset($attributeMap[$type])) {
            return Response::text('Invalid type. Valid types: '.implode(', ', array_keys($attributeMap)));
        }

        $filteredMap = $type ? [$type => $attributeMap[$type]] : $attributeMap;
        $components = [];

        foreach ($filteredMap as $typeName => $attributeClass) {
            $components[$typeName] = $this->extractByAttribute($discovered, $attributeClass);
        }

        $summary = collect($components)->map(fn (array $items): int => count($items))->all();

        return Response::json([
            'summary' => $summary,
            'components' => $components,
        ]);
    }

    /**
     * @return array<int, array{class: string, arguments: array<string, mixed>}>
     */
    private function extractByAttribute(array $discovered, string $attributeClass): array
    {
        $results = [];

        foreach ($discovered as $class => $attributes) {
            if (! is_array($attributes)) {
                continue;
            }

            foreach ($attributes as $attribute) {
                if (! is_array($attribute)) {
                    continue;
                }

                if (($attribute['attribute'] ?? null) === $attributeClass) {
                    $results[] = [
                        'class' => $class,
                        'method' => $attribute['method'] ?? null,
                        'arguments' => $attribute['arguments'] ?? [],
                    ];
                }
            }
        }

        return $results;
    }
}
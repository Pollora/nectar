<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Pollora\Discovery\Application\Services\DiscoveryManager;

#[IsReadOnly]
class DiscoveredComponents extends Tool
{
    protected string $name = 'discovered_components';

    protected string $description = 'List all auto-discovered Pollora components from the discovery system, grouped by type: post types, taxonomies, hooks (actions/filters), schedules, and REST routes.';

    /**
     * Mapping from tool filter types to discovery identifiers.
     *
     * @var array<string, string>
     */
    private const TYPE_MAP = [
        'post_type' => 'post_types',
        'taxonomy' => 'taxonomies',
        'hook' => 'hooks',
        'schedule' => 'schedules',
        'rest_route' => 'wp_rest_routes',
    ];

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->description('Filter by component type: "post_type", "taxonomy", "hook", "schedule", "rest_route". Omit for all.'),
        ];
    }

    public function handle(Request $request): Response
    {
        if (! app()->bound(DiscoveryManager::class)) {
            return Response::text('Discovery system is not available. Ensure Pollora framework is properly installed.');
        }

        $type = $request->get('type');

        if ($type && ! isset(self::TYPE_MAP[$type])) {
            return Response::text('Invalid type. Valid types: '.implode(', ', array_keys(self::TYPE_MAP)));
        }

        /** @var DiscoveryManager $discoveryManager */
        $discoveryManager = app(DiscoveryManager::class);

        $filteredMap = $type
            ? [$type => self::TYPE_MAP[$type]]
            : self::TYPE_MAP;

        $components = [];

        foreach ($filteredMap as $typeName => $discoveryId) {
            try {
                $discoveredItems = $discoveryManager->getDiscoveredItems($discoveryId);
                $components[$typeName] = $this->extractComponents($discoveredItems, $typeName);
            } catch (\Throwable) {
                $components[$typeName] = [];
            }
        }

        $summary = collect($components)->map(fn (array $items): int => count($items))->all();

        return Response::json([
            'summary' => $summary,
            'components' => $components,
        ]);
    }

    /**
     * @param  iterable<array<string, mixed>>  $discoveredItems
     * @return array<int, array{class: string, method: string|null, details: array<string, mixed>}>
     */
    private function extractComponents(iterable $discoveredItems, string $typeName): array
    {
        $results = [];

        foreach ($discoveredItems as $item) {
            $entry = [
                'class' => $item['class'] ?? 'unknown',
                'method' => $item['method'] ?? null,
            ];

            try {
                $attribute = $item['attribute']->newInstance();
                $entry['details'] = $this->extractDetails($attribute, $typeName, $item);
            } catch (\Throwable) {
                $entry['details'] = [];
            }

            $results[] = $entry;
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function extractDetails(object $attribute, string $typeName, array $item): array
    {
        return match ($typeName) {
            'post_type' => [
                'slug' => $attribute->slug ?? null,
                'singular' => $attribute->singular ?? null,
                'plural' => $attribute->plural ?? null,
            ],
            'taxonomy' => [
                'slug' => $attribute->slug ?? null,
                'singular' => $attribute->singular ?? null,
                'object_type' => $attribute->objectType ?? null,
            ],
            'hook' => [
                'type' => $item['type'] ?? 'unknown',
                'hook' => $attribute->hook ?? 'unknown',
                'priority' => $attribute->priority ?? 10,
            ],
            'schedule' => [
                'hook' => $attribute->hook ?? null,
                'recurrence' => $this->formatRecurrence($attribute->recurrence ?? null),
            ],
            'rest_route' => [
                'namespace' => $attribute->namespace ?? '',
                'route' => $attribute->route ?? '',
                'permission_callback' => $attribute->permissionCallback ?? null,
            ],
            default => [],
        };
    }

    private function formatRecurrence(mixed $recurrence): string
    {
        if (is_string($recurrence)) {
            return $recurrence;
        }

        if (is_array($recurrence)) {
            return $recurrence['display'] ?? json_encode($recurrence);
        }

        if (is_object($recurrence) && enum_exists($recurrence::class)) {
            return $recurrence->value ?? $recurrence->name;
        }

        return (string) $recurrence;
    }
}

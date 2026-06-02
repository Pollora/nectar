<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class TaxonomiesInfo extends Tool
{
    protected string $name = 'taxonomies_info';

    protected string $description = 'List all registered WordPress taxonomies with their associated post types and configuration.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filter' => $schema->string()
                ->description('Filter by taxonomy slug. Omit for all taxonomies.'),
            'include_builtin' => $schema->boolean()
                ->description('Include WordPress built-in taxonomies (category, post_tag). Defaults to false.'),
        ];
    }

    public function handle(Request $request): Response
    {
        if (! function_exists('get_taxonomies')) {
            return Response::text('WordPress is not loaded in this context.');
        }

        $filter = $request->get('filter');
        $includeBuiltin = $request->get('include_builtin', false);

        $args = $includeBuiltin ? [] : ['_builtin' => false];
        $taxonomies = get_taxonomies($args, 'objects');

        if ($filter) {
            $taxonomies = array_filter(
                $taxonomies,
                fn (object $tax): bool => str_contains($tax->name, $filter)
            );
        }

        $result = [];
        foreach ($taxonomies as $taxonomy) {
            $result[$taxonomy->name] = [
                'label' => $taxonomy->label,
                'singular' => $taxonomy->labels->singular_name ?? $taxonomy->label,
                'object_types' => $taxonomy->object_type,
                'public' => $taxonomy->public,
                'hierarchical' => $taxonomy->hierarchical,
                'show_in_rest' => $taxonomy->show_in_rest,
                'rewrite' => $taxonomy->rewrite,
            ];
        }

        return Response::json($result);
    }
}
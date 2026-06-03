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
class PostTypesInfo extends Tool
{
    protected string $name = 'post_types_info';

    protected string $description = 'List all registered WordPress post types with their configuration. Use "filter" to get details for a specific post type, or omit for a summary of all.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filter' => $schema->string()
                ->description('Filter by post type slug (e.g., "book", "product"). Omit for all post types.'),
            'include_builtin' => $schema->boolean()
                ->description('Include WordPress built-in post types (post, page, attachment, etc.). Defaults to false.'),
        ];
    }

    public function handle(Request $request): Response
    {
        if (! function_exists('get_post_types')) {
            return Response::text('WordPress is not loaded in this context.');
        }

        $filter = $request->get('filter');
        $includeBuiltin = $request->get('include_builtin', false);

        $args = $includeBuiltin ? [] : ['_builtin' => false];
        $postTypes = get_post_types($args, 'objects');

        if ($filter) {
            $postTypes = array_filter(
                $postTypes,
                fn (object $pt): bool => str_contains($pt->name, $filter)
            );
        }

        $result = [];
        foreach ($postTypes as $postType) {
            $result[$postType->name] = [
                'label' => $postType->label,
                'singular' => $postType->labels->singular_name ?? $postType->label,
                'public' => $postType->public,
                'publicly_queryable' => $postType->publicly_queryable,
                'show_in_rest' => $postType->show_in_rest,
                'has_archive' => $postType->has_archive,
                'hierarchical' => $postType->hierarchical,
                'supports' => get_all_post_type_supports($postType->name),
                'taxonomies' => get_object_taxonomies($postType->name),
                'rewrite' => $postType->rewrite,
                'menu_icon' => $postType->menu_icon,
                'capability_type' => $postType->capability_type,
            ];
        }

        return Response::json($result);
    }
}

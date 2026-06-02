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
class WpOption extends Tool
{
    protected string $name = 'wp_option';

    protected string $description = 'Read a WordPress option value by key (read-only). Returns the option value or indicates if it does not exist.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'key' => $schema->string()
                ->description('The option key to retrieve (e.g., "blogname", "siteurl", "permalink_structure").')
                ->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        if (! function_exists('get_option')) {
            return Response::text('WordPress is not loaded in this context.');
        }

        $key = $request->get('key');

        if (! $key) {
            return Response::text('The "key" parameter is required.');
        }

        $sentinel = '__nectar_option_not_found__';
        $value = get_option($key, $sentinel);

        if ($value === $sentinel) {
            return Response::json([
                'key' => $key,
                'exists' => false,
                'value' => null,
            ]);
        }

        return Response::json([
            'key' => $key,
            'exists' => true,
            'value' => $value,
        ]);
    }
}
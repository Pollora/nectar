<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Pollora\Nectar\Mcp\Support\OptionSecretGuard;

#[IsReadOnly]
class WpOption extends Tool
{
    protected string $name = 'wp_option';

    protected string $description = 'Read a WordPress option value by key (read-only). Returns the option value or indicates if it does not exist. Options whose name looks like a secret (password, token, API key, salt...) are refused unless allowed in config, and secret-looking sub-keys are masked.';

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

        if (! is_string($key) || $key === '') {
            return Response::text('The "key" parameter is required.');
        }

        $guard = new OptionSecretGuard((array) config('nectar.wp_option.allow', []));

        if ($guard->isDenied($key)) {
            return Response::text($guard->denialMessage($key));
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
            'value' => $guard->redact($value),
        ]);
    }
}

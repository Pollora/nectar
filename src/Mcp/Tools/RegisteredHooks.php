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
class RegisteredHooks extends Tool
{
    protected string $name = 'registered_hooks';

    protected string $description = 'List hooks discovered via #[Action] and #[Filter] attributes in the Pollora discovery cache, including class, method, priority, and hook name.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->description('Filter by hook type: "action" or "filter". Omit for both.'),
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

        $hooks = [];

        if (! $type || $type === 'action') {
            $hooks['actions'] = $this->extractHooks($discovered, 'Pollora\Attributes\Action');
        }

        if (! $type || $type === 'filter') {
            $hooks['filters'] = $this->extractHooks($discovered, 'Pollora\Attributes\Filter');
        }

        return Response::json($hooks);
    }

    /**
     * @return array<int, array{class: string, method: string, hook: string, priority: int}>
     */
    private function extractHooks(array $discovered, string $attributeClass): array
    {
        $hooks = [];

        foreach ($discovered as $class => $attributes) {
            if (! is_array($attributes)) {
                continue;
            }

            foreach ($attributes as $attribute) {
                if (! is_array($attribute) || ($attribute['attribute'] ?? null) !== $attributeClass) {
                    continue;
                }

                $hooks[] = [
                    'class' => $class,
                    'method' => $attribute['method'] ?? 'unknown',
                    'hook' => $attribute['arguments']['hook'] ?? $attribute['arguments'][0] ?? 'unknown',
                    'priority' => $attribute['arguments']['priority'] ?? $attribute['arguments'][1] ?? 10,
                ];
            }
        }

        return $hooks;
    }
}
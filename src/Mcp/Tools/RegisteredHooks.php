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
class RegisteredHooks extends Tool
{
    protected string $name = 'registered_hooks';

    protected string $description = 'List hooks discovered via #[Action] and #[Filter] attributes in the Pollora discovery system, including class, method, priority, and hook name.';

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
        if (! app()->bound(DiscoveryManager::class)) {
            return Response::text('Discovery system is not available. Ensure Pollora framework is properly installed.');
        }

        $type = $request->get('type');

        /** @var DiscoveryManager $discoveryManager */
        $discoveryManager = app(DiscoveryManager::class);

        try {
            $discoveredItems = $discoveryManager->getDiscoveredItems('hooks');
        } catch (\Throwable) {
            return Response::text('Unable to retrieve discovered hooks. Run "php artisan discovery:run" to generate the discovery cache.');
        }

        $hooks = [];

        foreach ($discoveredItems as $item) {
            $hookType = $item['type'] ?? 'unknown';

            if ($type && $type !== $hookType) {
                continue;
            }

            try {
                $attribute = $item['attribute']->newInstance();
                $hookName = $attribute->hook;
                $priority = $attribute->priority ?? 10;
            } catch (\Throwable) {
                $hookName = 'unknown';
                $priority = 10;
            }

            $key = $hookType === 'action' ? 'actions' : 'filters';
            $hooks[$key][] = [
                'class' => $item['class'] ?? 'unknown',
                'method' => $item['method'] ?? 'unknown',
                'hook' => $hookName,
                'priority' => $priority,
            ];
        }

        if (! $type || $type === 'action') {
            $hooks['actions'] ??= [];
        }

        if (! $type || $type === 'filter') {
            $hooks['filters'] ??= [];
        }

        return Response::json($hooks);
    }
}

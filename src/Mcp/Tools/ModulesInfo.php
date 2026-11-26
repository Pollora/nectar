<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Pollora\Nectar\Support\Value;

#[IsReadOnly]
class ModulesInfo extends Tool
{
    protected string $name = 'modules_info';

    protected string $description = 'List installed Laravel Modules (nwidart) with their status, path, and providers.';

    public function handle(Request $request): Response
    {
        if (! app()->bound('modules')) {
            return Response::json([
                'available' => false,
                'message' => 'Laravel Modules (nwidart) is not installed or registered.',
            ]);
        }

        $all = Value::ask(app('modules'), 'all');
        $modules = [];

        foreach (is_iterable($all) ? $all : [] as $module) {
            $name = Value::text(Value::ask($module, 'getName'));
            $modules[$name] = [
                'name' => $name,
                'path' => str_replace(base_path().'/', '', Value::text(Value::ask($module, 'getPath'))),
                'enabled' => Value::ask($module, 'isEnabled'),
            ];
        }

        return Response::json([
            'available' => true,
            'count' => count($modules),
            'modules' => $modules,
        ]);
    }
}

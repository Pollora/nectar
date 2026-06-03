<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

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

        $moduleManager = app('modules');
        $modules = [];

        foreach ($moduleManager->all() as $module) {
            $modules[$module->getName()] = [
                'name' => $module->getName(),
                'path' => str_replace(base_path().'/', '', $module->getPath()),
                'enabled' => $module->isEnabled(),
            ];
        }

        return Response::json([
            'available' => true,
            'count' => count($modules),
            'modules' => $modules,
        ]);
    }
}

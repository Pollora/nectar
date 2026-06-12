<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp;

use Laravel\Mcp\Server;
use Pollora\Nectar\Mcp\Prompts\UpgradePolloraV13\UpgradePolloraV13;
use Pollora\Nectar\Mcp\Tools\ActiveThemeInfo;
use Pollora\Nectar\Mcp\Tools\DiscoveredComponents;
use Pollora\Nectar\Mcp\Tools\ModulesInfo;
use Pollora\Nectar\Mcp\Tools\PolloraStatus;
use Pollora\Nectar\Mcp\Tools\PostTypesInfo;
use Pollora\Nectar\Mcp\Tools\RegisteredHooks;
use Pollora\Nectar\Mcp\Tools\TaxonomiesInfo;
use Pollora\Nectar\Mcp\Tools\WordPressInfo;
use Pollora\Nectar\Mcp\Tools\WordPressRoutes;
use Pollora\Nectar\Mcp\Tools\WpOption;

class Nectar extends Server
{
    protected string $name = 'Pollora Nectar';

    protected string $version = '0.2.0';

    protected string $instructions = 'Pollora Nectar provides WordPress and Pollora-specific development tools. Use these tools to inspect WordPress configuration, registered post types, taxonomies, hooks, routes, and the active theme. These tools complement Laravel Boost with WordPress/Pollora context. Upgrade prompts are available when a newer Pollora version is detected.';

    protected array $tools = [
        PolloraStatus::class,
        WordPressInfo::class,
        PostTypesInfo::class,
        TaxonomiesInfo::class,
        RegisteredHooks::class,
        ActiveThemeInfo::class,
        DiscoveredComponents::class,
        WordPressRoutes::class,
        ModulesInfo::class,
        WpOption::class,
    ];

    protected array $prompts = [
        UpgradePolloraV13::class,
    ];
}

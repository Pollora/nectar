<?php

declare(strict_types=1);

use Pollora\Nectar\Mcp\Nectar;
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

it('registers all 10 tools', function () {
    $reflection = new ReflectionClass(Nectar::class);
    $property = $reflection->getProperty('tools');
    $tools = $property->getDefaultValue();

    expect($tools)->toHaveCount(10)
        ->toContain(PolloraStatus::class)
        ->toContain(WordPressInfo::class)
        ->toContain(PostTypesInfo::class)
        ->toContain(TaxonomiesInfo::class)
        ->toContain(RegisteredHooks::class)
        ->toContain(ActiveThemeInfo::class)
        ->toContain(DiscoveredComponents::class)
        ->toContain(WordPressRoutes::class)
        ->toContain(ModulesInfo::class)
        ->toContain(WpOption::class);
});

it('has correct server metadata', function () {
    $reflection = new ReflectionClass(Nectar::class);

    $name = $reflection->getProperty('name')->getDefaultValue();
    $version = $reflection->getProperty('version')->getDefaultValue();

    expect($name)->toBe('Pollora Nectar');
    expect($version)->toBe('0.1.0');
});

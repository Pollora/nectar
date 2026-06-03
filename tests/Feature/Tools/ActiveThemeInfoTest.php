<?php

declare(strict_types=1);

use Pollora\Nectar\Mcp\Nectar;
use Pollora\Nectar\Mcp\Tools\ActiveThemeInfo;

beforeEach(function () {
    $themePath = base_path('themes/test-theme');

    // Create theme directory structure
    @mkdir("{$themePath}/config", 0777, true);
    @mkdir("{$themePath}/app/Providers", 0777, true);
    @mkdir("{$themePath}/resources/blocks/hero", 0777, true);
    @mkdir("{$themePath}/resources/views/partials", 0777, true);

    file_put_contents("{$themePath}/config/menus.php", '<?php return [];');
    file_put_contents("{$themePath}/config/supports.php", '<?php return [];');
    file_put_contents("{$themePath}/app/Providers/ThemeServiceProvider.php", '<?php');
    file_put_contents("{$themePath}/resources/views/index.blade.php", '<div></div>');
    file_put_contents("{$themePath}/resources/views/partials/header.blade.php", '<header></header>');
    file_put_contents("{$themePath}/vite.config.js", 'export default {}');
    file_put_contents("{$themePath}/package.json", '{"dependencies": {"tailwindcss": "^4.0"}}');
    file_put_contents("{$themePath}/theme.json", '{}');
});

afterEach(function () {
    $themePath = base_path('themes/test-theme');

    // Cleanup recursively
    $cleanup = function (string $dir) use (&$cleanup): void {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = "{$dir}/{$item}";
            is_dir($path) ? $cleanup($path) : unlink($path);
        }
        rmdir($dir);
    };

    $cleanup(base_path('themes'));
});

it('returns theme structure information', function () {
    Nectar::tool(ActiveThemeInfo::class)
        ->assertOk()
        ->assertSee('test-theme')
        ->assertSee('themes/test-theme');
});

it('lists config files', function () {
    Nectar::tool(ActiveThemeInfo::class)
        ->assertOk()
        ->assertSee('menus.php')
        ->assertSee('supports.php');
});

it('lists service providers', function () {
    Nectar::tool(ActiveThemeInfo::class)
        ->assertOk()
        ->assertSee('ThemeServiceProvider');
});

it('lists blocks', function () {
    Nectar::tool(ActiveThemeInfo::class)
        ->assertOk()
        ->assertSee('hero');
});

it('lists blade templates recursively', function () {
    Nectar::tool(ActiveThemeInfo::class)
        ->assertOk()
        ->assertSee('index')
        ->assertSee('partials/header');
});

it('detects vite and tailwind', function () {
    Nectar::tool(ActiveThemeInfo::class)
        ->assertOk()
        ->assertSee('has_vite')
        ->assertSee('has_tailwind');
});

it('computes theme namespace', function () {
    Nectar::tool(ActiveThemeInfo::class)
        ->assertOk()
        ->assertSee('Theme\\\\TestTheme\\\\');
});

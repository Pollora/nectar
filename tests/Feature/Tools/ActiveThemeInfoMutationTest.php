<?php

declare(strict_types=1);

use Laravel\Mcp\Request;
use Pollora\Nectar\Mcp\Tools\ActiveThemeInfo;

/**
 * @return array<string, mixed>
 */
function activeThemeMutationPayload(): array
{
    return json_decode((string) (new ActiveThemeInfo)->handle(new Request([]))->content(), true);
}

function activeThemeMutationWrite(string $path, string $content = ''): void
{
    $file = base_path('themes/test-theme/'.$path);
    @mkdir(dirname($file), 0777, true);
    file_put_contents($file, $content);
}

function activeThemeMutationRemove(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        is_dir("{$dir}/{$item}") ? activeThemeMutationRemove("{$dir}/{$item}") : unlink("{$dir}/{$item}");
    }

    rmdir($dir);
}

beforeEach(function (): void {
    @mkdir(base_path('themes/test-theme'), 0777, true);
});

afterEach(function (): void {
    activeThemeMutationRemove(base_path('themes'));
});

it('describes a bare theme key by key', function (): void {
    expect(activeThemeMutationPayload())->toBe([
        'name' => 'test-theme',
        'path' => 'themes/test-theme',
        'config_files' => [],
        'service_providers' => [],
        'blocks' => [],
        'blocks_in_deprecated_directory' => [],
        'blade_templates' => [],
        'has_vite' => false,
        'has_package_json' => false,
        'has_theme_json' => false,
        'has_tailwind' => false,
        'namespace' => 'Theme\\TestTheme\\',
    ]);
});

it('detects Vite from either configuration file', function (string $file, bool $expected): void {
    activeThemeMutationWrite($file, 'export default {}');

    expect(activeThemeMutationPayload()['has_vite'])->toBe($expected);
})->with([
    'javascript' => ['vite.config.js', true],
    'typescript' => ['vite.config.ts', true],
    'something else' => ['vite.config.mjs', false],
]);

it('detects Tailwind from its configuration or from the package manifest', function (string $file, string $content, bool $expected): void {
    activeThemeMutationWrite($file, $content);

    expect(activeThemeMutationPayload()['has_tailwind'])->toBe($expected);
})->with([
    'config.js' => ['tailwind.config.js', '', true],
    'config.ts' => ['tailwind.config.ts', '', true],
    'package.json that depends on it' => ['package.json', '{"dependencies":{"tailwindcss":"^4"}}', true],
    'package.json that does not' => ['package.json', '{"dependencies":{}}', false],
]);

it('reports the package manifest and the theme.json', function (): void {
    activeThemeMutationWrite('package.json', '{}');
    activeThemeMutationWrite('theme.json', '{}');

    expect(activeThemeMutationPayload())->toMatchArray(['has_package_json' => true, 'has_theme_json' => true]);
});

it('names Blade templates by their path under resources/views, without the extension', function (): void {
    activeThemeMutationWrite('resources/views/index.blade.php');
    activeThemeMutationWrite('resources/views/notes.txt');
    activeThemeMutationWrite('resources/views/partials/header.blade.php');
    activeThemeMutationWrite('resources/views/partials/forms/field.blade.php');

    $templates = activeThemeMutationPayload()['blade_templates'];
    sort($templates);

    expect($templates)->toBe(['index', 'partials/forms/field', 'partials/header']);
});

it('lists the providers of the theme, only the PHP files', function (): void {
    activeThemeMutationWrite('app/Providers/ThemeServiceProvider.php');
    activeThemeMutationWrite('app/Providers/README.md');

    expect(activeThemeMutationPayload()['service_providers'])->toBe(['ThemeServiceProvider']);
});

it('says so when the theme directory does not exist', function (): void {
    activeThemeMutationRemove(base_path('themes'));

    expect((string) (new ActiveThemeInfo)->handle(new Request([]))->content())->toBe('Theme directory not found at: themes/test-theme');
});

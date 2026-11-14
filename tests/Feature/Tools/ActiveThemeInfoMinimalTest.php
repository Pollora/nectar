<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use Pollora\Nectar\Mcp\Tools\ActiveThemeInfo;

/**
 * @return array<string, mixed>|string
 */
function activeThemePayload(): array|string
{
    $content = (string) (new ActiveThemeInfo)->handle(new Request([]))->content();

    return json_decode($content, true) ?? $content;
}

beforeEach(function (): void {
    $this->themesPath = base_path('themes');
    File::deleteDirectory($this->themesPath);
});

afterEach(function (): void {
    File::deleteDirectory($this->themesPath);
});

it('says where the active theme was expected when its directory is missing', function (): void {
    expect(activeThemePayload())->toBe('Theme directory not found at: themes/test-theme');
});

it('describes a theme that has nothing but its directory', function (): void {
    File::makeDirectory($this->themesPath.'/test-theme', 0777, true);

    expect(activeThemePayload())->toMatchArray([
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

it('does not take a package.json without tailwindcss for a tailwind theme', function (): void {
    File::makeDirectory($this->themesPath.'/test-theme', 0777, true);
    File::put($this->themesPath.'/test-theme/package.json', '{"dependencies": {"vite": "^7.0"}}');

    expect(activeThemePayload())->toMatchArray(['has_package_json' => true, 'has_tailwind' => false]);
});

it('detects tailwind from its config file', function (): void {
    File::makeDirectory($this->themesPath.'/test-theme', 0777, true);
    File::put($this->themesPath.'/test-theme/tailwind.config.ts', 'export default {}');

    expect(activeThemePayload())->toMatchArray(['has_tailwind' => true]);
});

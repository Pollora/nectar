<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Tools;

use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ActiveThemeInfo extends Tool
{
    protected string $name = 'active_theme_info';

    protected string $description = 'Get details about the active Pollora theme: directory structure, service providers, config files, Vite status, and registered blocks.';

    public function handle(Request $request): Response
    {
        $themeName = function_exists('get_stylesheet') ? get_stylesheet() : null;

        if (! $themeName) {
            return Response::text('No active theme found.');
        }

        $themePath = base_path("themes/{$themeName}");

        if (! File::isDirectory($themePath)) {
            return Response::text("Theme directory not found at: themes/{$themeName}");
        }

        $configFiles = File::isDirectory("{$themePath}/config")
            ? collect(File::files("{$themePath}/config"))->map(fn ($f) => $f->getFilename())->all()
            : [];

        $providers = $this->findProviders($themePath);

        $blocks = File::isDirectory("{$themePath}/resources/blocks")
            ? collect(File::directories("{$themePath}/resources/blocks"))
                ->map(fn (string $dir): string => basename($dir))
                ->all()
            : [];

        $views = File::isDirectory("{$themePath}/resources/views")
            ? $this->listBladeTemplates("{$themePath}/resources/views")
            : [];

        $hasVite = File::exists("{$themePath}/vite.config.js") || File::exists("{$themePath}/vite.config.ts");
        $hasPackageJson = File::exists("{$themePath}/package.json");
        $hasThemeJson = File::exists("{$themePath}/theme.json");
        $hasTailwind = File::exists("{$themePath}/tailwind.config.js")
            || File::exists("{$themePath}/tailwind.config.ts")
            || ($hasPackageJson && str_contains(File::get("{$themePath}/package.json"), 'tailwindcss'));

        return Response::json([
            'name' => $themeName,
            'path' => "themes/{$themeName}",
            'config_files' => $configFiles,
            'service_providers' => $providers,
            'blocks' => $blocks,
            'blade_templates' => $views,
            'has_vite' => $hasVite,
            'has_package_json' => $hasPackageJson,
            'has_theme_json' => $hasThemeJson,
            'has_tailwind' => $hasTailwind,
            'namespace' => 'Theme\\'.str_replace('-', '', ucwords($themeName, '-')).'\\',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function findProviders(string $themePath): array
    {
        $providersDir = "{$themePath}/app/Providers";

        if (! File::isDirectory($providersDir)) {
            return [];
        }

        return collect(File::files($providersDir))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->map(fn ($file) => $file->getFilenameWithoutExtension())
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function listBladeTemplates(string $viewsPath, string $prefix = ''): array
    {
        $templates = [];

        foreach (File::files($viewsPath) as $file) {
            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $name = $prefix.str_replace('.blade.php', '', $file->getFilename());
                $templates[] = $name;
            }
        }

        foreach (File::directories($viewsPath) as $dir) {
            $dirName = basename($dir);
            $templates = array_merge(
                $templates,
                $this->listBladeTemplates($dir, $prefix.$dirName.'/')
            );
        }

        return $templates;
    }
}
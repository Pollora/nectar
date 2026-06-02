<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class WordPressInfo extends Tool
{
    protected string $name = 'wordpress_info';

    protected string $description = 'Get WordPress information: version, active/inactive plugins, active theme, multisite status, and key constants.';

    public function handle(Request $request): Response
    {
        if (! defined('ABSPATH')) {
            return Response::text('WordPress is not loaded in this context.');
        }

        $plugins = function_exists('get_plugins') ? get_plugins() : [];
        $activePlugins = function_exists('get_option') ? (array) get_option('active_plugins', []) : [];

        $pluginList = collect($plugins)->map(fn (array $plugin, string $file): array => [
            'name' => $plugin['Name'] ?? $file,
            'version' => $plugin['Version'] ?? 'unknown',
            'active' => in_array($file, $activePlugins, true),
        ])->values()->all();

        $constants = [];
        $checkConstants = [
            'WP_DEBUG', 'WP_DEBUG_LOG', 'MULTISITE', 'WP_ALLOW_MULTISITE',
            'DISALLOW_FILE_MODS', 'DISALLOW_FILE_EDIT', 'WP_AUTO_UPDATE_CORE',
            'DISABLE_WP_CRON', 'WP_MEMORY_LIMIT', 'WP_MAX_MEMORY_LIMIT',
            'WP_POST_REVISIONS', 'AUTOSAVE_INTERVAL', 'WP_CONTENT_DIR',
        ];

        foreach ($checkConstants as $constant) {
            if (defined($constant)) {
                $constants[$constant] = constant($constant);
            }
        }

        return Response::json([
            'version' => get_bloginfo('version'),
            'site_url' => get_site_url(),
            'home_url' => get_home_url(),
            'is_multisite' => is_multisite(),
            'active_theme' => get_stylesheet(),
            'parent_theme' => get_template() !== get_stylesheet() ? get_template() : null,
            'plugins' => $pluginList,
            'constants' => $constants,
            'locale' => get_locale(),
            'permalink_structure' => get_option('permalink_structure', ''),
        ]);
    }
}
<p align="center">
  <a href="https://github.com/Pollora/nectar">
    <img src="resources/images/pollora-logo.svg" width="400" alt="Pollora">
  </a>
</p>

<p align="center">
  <strong>Nectar</strong> — AI-powered development context for Pollora
</p>

<p align="center">
  <a href="https://packagist.org/packages/pollora/nectar"><img src="https://img.shields.io/packagist/v/pollora/nectar" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/pollora/nectar"><img src="https://img.shields.io/packagist/dt/pollora/nectar" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/pollora/nectar"><img src="https://img.shields.io/packagist/l/pollora/nectar" alt="License"></a>
</p>

## About Nectar

Nectar feeds AI agents with the context they need to write high-quality Pollora code. Built on top of [Laravel Boost](https://laravel.com/docs/boost), it provides **AI guidelines**, **agent skills**, and a **dedicated MCP server** tailored to the Pollora framework and WordPress integration.

### What it provides

| Feature | Description |
|---------|-------------|
| **AI Guidelines** | Pollora architecture, PHP attributes, WordPress routing, Blade theming — injected into your agent's context via Boost |
| **8 Agent Skills** | On-demand knowledge for post types, taxonomies, theming, hooks, blocks, REST API, scheduling, and modules |
| **10 MCP Tools** | Live introspection of your WordPress & Pollora environment directly from your AI agent |

## Installation

```bash
composer require pollora/nectar --dev
```

Then run Boost to install guidelines and skills:

```bash
php artisan boost:install
```

Select `pollora/nectar` when prompted for third-party packages, or add it manually to `boost.json`:

```json
{
    "packages": ["pollora/nectar"]
}
```

Then update:

```bash
php artisan boost:update
```

## MCP Server

Nectar registers a `pollora-nectar` MCP server that gives AI agents live access to your WordPress and Pollora environment.

### Starting the server

```bash
php artisan nectar:mcp
```

Or register it in your `.mcp.json`:

```json
{
    "mcpServers": {
        "pollora-nectar": {
            "command": "php",
            "args": ["artisan", "nectar:mcp"]
        }
    }
}
```

### Available MCP Tools

| Tool | Description |
|------|-------------|
| `pollora_status` | PHP, Laravel, Pollora & WordPress versions, active theme, discovery cache status |
| `wordpress_info` | WordPress version, plugins, theme, multisite status, constants, locale |
| `post_types_info` | All registered custom post types with supports, taxonomies, and configuration |
| `taxonomies_info` | All registered taxonomies with associated post types |
| `registered_hooks` | Hooks discovered via `#[Action]` and `#[Filter]` attributes |
| `active_theme_info` | Theme structure, service providers, config files, Vite/Tailwind status, blocks |
| `discovered_components` | All auto-discovered components grouped by type (post types, taxonomies, hooks, schedules, REST routes) |
| `wordpress_routes` | All routes including `Route::wp()` with WordPress conditions and middleware |
| `modules_info` | Installed Laravel Modules with status |
| `wp_option` | Read any WordPress option by key |

## AI Guidelines

Guidelines are loaded automatically when Boost runs. They cover:

- Pollora architecture (Laravel + WordPress bridge)
- PHP 8 attributes (`#[PostType]`, `#[Taxonomy]`, `#[Action]`, `#[Filter]`, `#[Schedule]`, `#[WpRestRoute]`)
- WordPress routing with `Route::wp()` and template hierarchy
- Blade templating with Sage Directives
- Theme development conventions
- Available Artisan commands

## Agent Skills

Skills are activated on-demand when working on specific tasks:

| Skill | When it activates |
|-------|-------------------|
| `pollora-post-types` | Creating custom post types with attributes |
| `pollora-taxonomies` | Creating custom taxonomies |
| `pollora-theming` | Theme development (Blade, Vite, Tailwind, assets) |
| `pollora-hooks` | Registering WordPress actions & filters |
| `pollora-blocks` | Gutenberg block development with JSX & Tailwind |
| `pollora-rest-api` | REST API endpoints with `#[WpRestRoute]` |
| `pollora-scheduling` | Scheduled tasks with `#[Schedule]` |
| `pollora-modules` | Laravel Modules with auto-discovery |

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=nectar-config
```

```php
// config/nectar.php
return [
    'enabled' => env('NECTAR_ENABLED', true),

    'mcp' => [
        'tools' => [
            'exclude' => [],   // Tool class names to exclude
            'include' => [],   // Additional tool class names to include
        ],
    ],
];
```

## Requirements

- PHP ^8.3
- Laravel 13.x
- Pollora Framework ^13.0
- Laravel Boost ^2.0

## License

Nectar is open-sourced software licensed under the [GPL-2.0-or-later](LICENSE).
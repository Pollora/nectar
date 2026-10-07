<p align="center">
  <a href="https://pollora.dev">
    <img src="https://raw.githubusercontent.com/Pollora/.github/main/brand/banners/nectar.png" width="100%" alt="Nectar: AI development context for Pollora">
  </a>
</p>

<p align="center">
  <a href="https://packagist.org/packages/pollora/nectar"><img src="https://img.shields.io/packagist/v/pollora/nectar" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/pollora/nectar"><img src="https://img.shields.io/packagist/dt/pollora/nectar" alt="Total Downloads"></a>
  <a href="https://github.com/Pollora/nectar/actions/workflows/ci.yml"><img src="https://github.com/Pollora/nectar/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
  <a href="LICENSE"><img src="https://img.shields.io/github/license/Pollora/nectar" alt="License"></a>
</p>

Nectar gives AI coding agents the context they need to write correct Pollora code. Built on [Laravel Boost](https://laravel.com/docs/boost), it adds Pollora guidelines, on-demand agent skills and an MCP server that reads your live WordPress and Pollora environment. Your agent stops guessing at `register_post_type()` calls and uses the framework's attributes, routes and commands instead.

> Part of [Pollora](https://pollora.dev), the Laravel framework for WordPress. Install it as a dev dependency in any Pollora project.

## Installation

Requires PHP 8.3+, Laravel 12.x or 13.x, Pollora Framework 12.x or 13.x and Laravel Boost 2.x.

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

## What it provides

| Feature | Description |
|---------|-------------|
| **AI Guidelines** | Pollora architecture, PHP attributes, WordPress routing, Blade theming — injected into your agent's context via Boost |
| **9 Agent Skills** | On-demand knowledge for post types, taxonomies, theming, hooks, blocks, REST API and AJAX, scheduling, modules, and abilities |
| **10 MCP Tools** | Live introspection of your WordPress & Pollora environment directly from your AI agent |
| **Upgrade Prompts** | Step-by-step MCP prompts for upgrading between Pollora versions (12 → 13, 13.x → 13.32) |

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
| `pollora-blocks` | Gutenberg blocks with JSX, Blade rendering & Tailwind |
| `pollora-rest-api` | REST API endpoints with `#[WpRestRoute]`, AJAX handlers with `#[Ajax]` |
| `pollora-scheduling` | Scheduled tasks with `#[Schedule]` |
| `pollora-abilities` | WordPress Abilities API with `#[Ability]` and the `Ability` facade |
| `pollora-modules` | Laravel Modules with auto-discovery |

## Upgrade Assistance

Nectar provides MCP upgrade prompts that guide AI agents through Pollora version upgrades. Prompts are **automatically registered** when the current project version matches.

| Prompt | Available when | Covers |
|--------|---------------|--------|
| `upgrade-pollora-v13` | Pollora 12.x detected | Loop facade removal, config registration removal, CSRF middleware rename, Vite theme.json setup, WordPress 7.0, and more |
| `upgrade-pollora-v13-32` | Pollora 13.x before 13.32 detected | Dependencies and composer-patches 2, renamed commands, extracted Hook/Option/Ajax packages, Loop/Query removal, blocks in `resources/views/blocks`, skeleton files (`routes/web.php`, cache table, `.htaccess`), theme.json from `@theme` |

The upgrade prompt includes:
- Step-by-step process (assess → safety net → analyze → apply → update deps → clean up)
- All breaking changes with search patterns and before/after code examples
- Theme build pipeline updates (`@roots/vite-plugin`, `wordpressThemeJson`)
- Post-upgrade cleanup (`wp transient delete --all`)

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

## Documentation

The full guide is on [pollora.dev/nectar/overview](https://pollora.dev/nectar/overview/).

## Testing

```bash
composer test
```

Runs Pint, PHPStan and the Pest suite.

## Contributing

Contributions are welcome: see the [contributing guide](https://github.com/Pollora/.github/blob/main/CONTRIBUTING.md). Report security issues privately, as described in the [security policy](https://github.com/Pollora/.github/blob/main/SECURITY.md).

## License

Nectar is open-source software licensed under the [MIT license](LICENSE). © [RuBee group](https://rubee.group)

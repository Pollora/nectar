# Changelog

All notable changes to `pollora/nectar` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Pollora 13.x → 13.32 upgrade prompt** (`upgrade-pollora-v13-32`), registered when Pollora 13.0 to 13.4.x is installed: dependencies and wp-packages, composer-patches 2 (`patches-relock`, `patches.lock.json`, checking that WordPress is patched), renamed Artisan commands, classes moved to `pollora/hook`, `pollora/option` and `pollora/ajax`, `Loop`/`Query` removal, `Translater` domain, blocks in `resources/views/blocks` and registered without a provider, skeleton files (`routes/web.php`, cache table, `.htaccess`), and the theme.json defects fixed in themes (`theme(static)`, the copy step, cyclic `@theme` values)
- **`pollora-abilities` skill**: the WordPress Abilities API with `#[Ability]` and the `Ability` facade
- `active_theme_info` reports `blocks_in_deprecated_directory`, the blocks still in `resources/blocks`

### Changed

- **Guidelines**: the facades that exist (no `Loop` or `Query`, which were removed; `Ability`, `PostQuery`, `MetaQuery`, `TaxQuery`, `Mail`, `Constant` added), `__()` routing, blocks, `pollora_register()`, the design tokens in `@theme`, the template marker, `#[Ajax]`, `#[Ability]`, `#[SkipDiscovery]`, and the full list of Artisan commands
- **13.x guidelines**: `illuminate/*` `^13.32`, WordPress 7, the extracted packages
- **`pollora-blocks` skill** rewritten for `resources/views/blocks`, dynamic Blade blocks by default (`render.blade.php`, `--static`), registration by Pollora without a service provider, and the migration from `resources/blocks`
- **`pollora-theming` skill**: `pollora_register()`, the login screen (`config/login.php`), `get_theme_file_uri()`, the template marker; `tailwind.config.js` dropped
- **`pollora-rest-api` skill**: AJAX handlers with `#[Ajax]`
- **12 → 13 upgrade prompt**: no longer adds a `copy-theme-json` build step, which froze a theme's palette; blocks section updated
- `pollora/framework` is constrained to `^12.0 || ^13.0` instead of `*`; `orchestra/testbench` accepts `^11.0`, so the tests run against Laravel 13 and framework 13 instead of Laravel 12 and framework 12.0

### Fixed

- `active_theme_info` listed no block for a theme made since v13.32: it read `resources/blocks` only. It reads `resources/views/blocks`, and counts a directory as a block only when it holds a `block.json`
- `pollora-post-types` skill: `pollora:make:post-type` (was `pollora:make-post-type`)

## [0.2.0] - 2026-06-23

### Added

- **Pollora 12→13 Upgrade Prompt** (`upgrade-pollora-v13`): Step-by-step MCP prompt for upgrading from Pollora 12.x to 13.x, automatically registered when Pollora 12.x is detected
  - Loop facade removal → Sage Directives migration
  - Config-based post type/taxonomy registration removal → attribute classes
  - `@theme` Blade directive removal (Tailwind CSS v4 conflict)
  - Route model namespace change (Domain → Infrastructure)
  - CSRF middleware rename (`ValidateCsrfToken` → `PreventRequestForgery`) with WordPress route exclusions
  - Theme Vite configuration (`wordpressThemeJson` plugin, font path resolution)
  - WordPress 7.0 update
  - Post-upgrade cleanup with transient flush
- **Pollora 12.x Guidelines** (`resources/boost/guidelines/12/core.blade.php`): Version-specific guidelines for projects still on Pollora 12, including deprecated features (Loop facade, `@theme` directive, config-based registration)

### Changed

- Bumped version to 0.2.0
- `orchestra/testbench` widened to `^9.0|^10.0` for Laravel 12 compatibility
- MCP server instructions updated to mention upgrade prompts

## [0.1.0] - 2025-06-01

### Added

- Initial release of Pollora Nectar
- **AI Guidelines**: Pollora architecture, PHP attributes, WordPress routing, Blade theming
- **8 Agent Skills**: post-types, taxonomies, theming, hooks, blocks, rest-api, scheduling, modules
- **10 MCP Tools**:
  - `pollora_status` — Framework versions, active theme, discovery cache status
  - `wordpress_info` — WordPress version, plugins, theme, multisite, constants
  - `post_types_info` — Registered custom post types with configuration
  - `taxonomies_info` — Registered taxonomies with associated post types
  - `registered_hooks` — Discovered `#[Action]` and `#[Filter]` hooks
  - `active_theme_info` — Theme structure, providers, Vite/Tailwind status
  - `discovered_components` — All auto-discovered components by type
  - `wordpress_routes` — Routes including `Route::wp()` with WordPress conditions
  - `modules_info` — Laravel Modules (nwidart) status
  - `wp_option` — Read WordPress options by key
- `nectar:mcp` Artisan command to start the MCP server
- Configuration file with tool include/exclude and WP-CLI allowed commands
- Auto-registration via Laravel package discovery
# Changelog

All notable changes to `pollora/nectar` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
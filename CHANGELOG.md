# Changelog

All notable changes to `pollora/nectar` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
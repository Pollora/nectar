# Changelog

All notable changes to `pollora/nectar` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.5.0] - 2026-10-07

### Added

- **Skill** `pollora-hooks`: asynchronous actions — `->async()` and `#[Async]`, their options, what travels and what is refused, `capture()` / `when()`, drivers and what must run them, the rules for a replayable handler, `Async::fake()`, `pollora:async:list` and `pollora:make:action --async`
- **Core and 13.x guidelines**: `#[Async]` in the attribute snippet, the `pollora:async:list` command, the async checks of `pollora:doctor`

## [1.4.0] - 2026-10-07

### Added

- **Skills** `pollora-typed-meta`, `pollora-roles` and `pollora-block-bindings`: `#[Meta]` and `Meta::of()`, `#[Role]` / `#[ModifyRole]` / `#[CapabilitySet]` and Laravel's Gate, `#[BlockBinding]` and the `pollora/*` sources, with their commands and doctor checks (framework v13.34.4 to v13.35)
- **Core guidelines**: the three features in the attribute snippet and the conventions, the `Meta` facade, and the commands `pollora:make:role`, `pollora:make:binding`, `pollora:meta:list|audit`, `pollora:roles:list|show|prune|import|dump`, `pollora:binding:list`
- **13.x guidelines**: typed meta, roles and Block Bindings; the request lifecycle (`template_redirect` after every provider has booted, since v13.35.1)

### Changed

- **Upgrade prompt** and 13.x guidelines: `^13.35` (Laravel 13.35)
- **REST skill**: the `Can` permission (a capability, a `#[CapabilitySet]` case, a meta capability on a request parameter)

### Fixed

- **REST skill**: the built-in permissions are imported from `Pollora\WpRest\Permissions` and the contract from `Pollora\Attributes\WpRestRoute\Permission`; the namespaces it gave did not exist

## [1.3.4] - 2026-10-07

### Changed

- **License**: Nectar is now MIT, like the framework, the skeleton and the other Pollora packages (was GPL-2.0-or-later)
- **README**: Pollora banner and badges, requirements up front, link to the pollora.dev guide, contributing and security footer

## [1.3.3] - 2026-10-02

### Changed

- **Core guidelines**: a new Pollora project needs PHP 8.4 (the skeleton's lock ships Symfony 8); the framework itself still supports 8.3 (Pollora/framework#301)

## [1.3.2] - 2026-09-30

### Changed

- **13.32 upgrade prompt, core guidelines and `pollora-blocks` skill**: Pollora v13.34.0 is a stable release — the prompt's constraint is `pollora/framework` `^13.34`, with no `@beta` flag

## [1.3.1] - 2026-09-30

### Changed

- **Core guidelines**: `pollora:doctor`, to run first when something fails without an error (Pollora/framework#365). Needs Pollora 13.34.0-beta.2

- **`pollora-theming` skill**: `pollora:make:theme` no longer always activates the generated theme — only on a site with no usable theme; `--activate` / `--no-activate` (Pollora/framework#364). Needs Pollora 13.34.0-beta.2

## [1.3.0] - 2026-09-29

### Changed

- **Guidelines and the 13.32 upgrade prompt**: Pollora v13.34.0-beta requires Laravel 13.34 (`illuminate/*` `^13.34`, `^13.32` before) — the framework's version tracks Laravel's. The prompt's example constraints are `laravel/framework` `^13.34` and `pollora/framework` `^13.34@beta`

- **`pollora-theming` skill and core guidelines**: block themes (Full Site Editing) are no longer ruled out. The skill documents them — `templates/` and `parts/` at the theme root, static patterns as native `patterns/*.php`, Blade patterns in `resources/views/patterns`, WordPress's pattern cache, the `template-canvas` marker — and lists the three `pollora:make:theme` templates, `magazine` (Buzz) included. Needs Pollora 13.34.0-beta, which ships the `magazine` template

## [1.2.0] - 2026-09-28

### Changed

- **`pollora-blocks` skill and core guidelines**: `<InnerBlocks />` in a block's `render.blade.php` (Pollora 13.32.0-beta.9), its options as attributes, `$isPreview`, and `pollora:make:block --inner-blocks`. A project on an earlier beta should not read them: it lacks the runtime they describe

## [1.1.1] - 2026-09-28

### Fixed

- A project got the 12.x **and** the 13.x guidelines, whatever its framework: Boost reads every file under `resources/boost/guidelines` for a package outside Laravel's own, subdirectories included, so `12/core` and `13/core` both landed in `AGENTS.md`. One `framework.blade.php` now picks its text by the installed framework major (a branch without an alias gets the 13.x text)
- The 12.x text printed `@@title` and `@@theme` instead of `@title` and `@theme`

## [1.1.0] - 2026-09-28

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

## [1.0.0] - 2026-07-02

First tagged release: the content of 0.2.0.

### Changed

- The guidelines and skills name the Artisan commands with the colon convention (`pollora:make:theme`…)
- The README documents the upgrade prompts and the requirements

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
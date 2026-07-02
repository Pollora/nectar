# Pollora 12 to 13 Upgrade Specialist

You are an expert Pollora upgrade specialist with deep knowledge of both Pollora 12.x (Laravel 12) and Pollora 13.x (Laravel 13). Your task is to systematically upgrade the application from Pollora 12 to 13 while ensuring all WordPress integration and Laravel functionality remains intact.

## Prerequisites: Laravel Upgrade First

**CRITICAL:** Pollora 13.x requires Laravel 13.x. Before applying Pollora-specific changes, you MUST first complete the Laravel 12 to 13 upgrade using the `upgrade-laravel-v13` prompt from Laravel Boost. The Laravel upgrade handles framework-level breaking changes (CSRF middleware rename, cache config, dependency updates, etc.).

Once the Laravel upgrade is complete, proceed with the Pollora-specific changes below.

## Upgrade Process

Follow this systematic process after the Laravel upgrade is complete:

### 1. Assess Current State

Before making any changes:

- Check `composer.json` for the current Pollora framework version constraint
- Identify usage of deprecated Pollora features:
  - `Loop` facade usage
  - `@theme` Blade directive usage
  - Config-based post type/taxonomy registration (`config/post-types.php`, `config/taxonomies.php`)
  - Route model imports from `Pollora\Route\Domain\Models\Route`
- Check PHP version (Pollora 13 requires PHP 8.3+)
- Run `php -v` to confirm PHP version

### 2. Create Safety Net

- Ensure you're working on a dedicated branch
- Run the existing test suite to establish baseline
- Note any custom code that extends Pollora framework classes

### 3. Analyze Codebase for Breaking Changes

Search the codebase for patterns affected by v13 changes, organized by priority:

**High Priority Searches:**
- `Loop::` and `use.*Pollora.*Loop` — Loop facade removed in v13.2
- `config/post-types.php` and `config/taxonomies.php` — Config-based registration removed in v13.4
- PHP version in CI/CD and server configs — Must be 8.3+

**Medium Priority Searches:**
- `@theme` in `.blade.php` files — Directive removed in v13.4 (conflicts with Tailwind CSS v4)
- `Pollora\Route\Domain\Models\Route` — Namespace moved to `Infrastructure` in v13.4

**Low Priority Searches:**
- `dev-main` dependencies in `composer.json` — All stabilized to proper version constraints in v13

### 4. Apply Changes Systematically

For each category of changes:

1. **Search** for affected patterns using grep/search tools
2. **List** all files that need modification
3. **Apply** the fix consistently across all occurrences
4. **Verify** each change doesn't break functionality

### 5. Update Dependencies

After code changes are complete, update the Pollora framework:

```bash
composer require pollora/framework:^13.0 --with-all-dependencies
```

### 6. Clean Up and Verify

After all changes and dependency updates:

```bash
php artisan optimize:clear
php artisan discovery:clear && php artisan discovery:run
wp transient delete --all
```

Then verify:

- Run the full test suite
- Verify WordPress admin is accessible
- Check that all custom post types and taxonomies are registered
- Verify theme templates render correctly (fonts, styles, layout)
- Test WordPress routing (`Route::wp()`)
- Verify `php artisan pollora:status` reports the correct version

## Execution Strategy

When upgrading, maximize efficiency by:

- **Complete the Laravel upgrade first** — Do not mix Laravel and Pollora changes
- **Batch similar changes** — Group all Loop facade replacements, then config migrations, etc.
- **Prioritize high-impact changes** that could cause immediate failures (Loop facade, config registration)
- **Test incrementally** — Verify after each category of changes


# Upgrading from Pollora 12.x to 13.x

> **Note:** We document every known breaking change. Since some changes are in specific parts of the framework, only a portion may affect your application.

## PHP Version Requirement

**Likelihood Of Impact: High**

Pollora 13.x requires **PHP 8.3** or higher. Pollora 12.x supported PHP 8.2+.

Verify your PHP version:

```bash
php -v
```

If running PHP 8.2, upgrade to PHP 8.3+ before proceeding.

## Updating Dependencies

**Likelihood Of Impact: High**

Update the following dependencies in your application's `composer.json` file:

@boostsnippet('Dependency Updates', 'json')
{
    "require": {
        "pollora/framework": "^13.0",
        "johnpbloch/wordpress": "^7.0"
    }
}
@endboostsnippet

Also update WordPress to the latest major version. Pollora 13.x supports WordPress 7.0+.

Key dependency changes in Pollora 13:
- `illuminate/*`: `^12.54` → `^13.0`
- `pollora/colt`: `^9.0` → `^10.0`
- `nwidart/laravel-modules`: `^12.0` → `^13.0`
- `symfony/process`: `^7.3` → `^8.0`
- `pollora/helper-overrider`: `dev-main` → `^1.0`
- `pollora/entity`: `dev-main` → `^1.2`
- `pollora/query`: `dev-main` → `^1.0`
- `spatie/php-structure-discoverer`: `dev-main` → `^2.4`

Run the update:

```bash
composer update
```

## Loop Facade Removal

**Likelihood Of Impact: High**

The `Loop` facade (`Pollora\Support\Facades\Loop` / `Pollora\View\Loop`) has been **completely removed** in Pollora 13.2.

### Search Patterns

Search for these patterns in your codebase:
- `Loop::` in PHP files
- `use Pollora\Support\Facades\Loop` or `use Pollora\View\Loop` in import statements

### Migration

Replace all `Loop` facade calls with [Sage Directives](https://log1x.github.io/sage-directives-docs/):

@boostsnippet('Loop Facade Migration', 'blade')
{{-- Pollora 12.x (REMOVED) --}}
{!! Loop::title() !!}
{!! Loop::content() !!}
{!! Loop::excerpt() !!}
{{ Loop::id() }}

{{-- Pollora 13.x --}}
@@title
@@content
@@excerpt
{{ get_the_ID() }}
@endboostsnippet

In PHP controllers or services, replace facade calls with WordPress functions directly:

@boostsnippet('Loop Facade PHP Migration', 'php')
// Pollora 12.x (REMOVED)
use Pollora\Support\Facades\Loop;
$title = Loop::title();
$content = Loop::content();

// Pollora 13.x
$title = get_the_title();
$content = apply_filters('the_content', get_the_content());
@endboostsnippet

## @@theme Blade Directive Removal

**Likelihood Of Impact: Medium**

The `@@theme` Blade directive has been **removed** in Pollora 13.4 because it conflicts with Tailwind CSS v4's `@@theme` at-rule.

### Search Patterns

Search for `@@theme` and `@@endtheme` in all `.blade.php` files.

### Migration

@boostsnippet('@theme Directive Migration', 'blade')
{{-- Pollora 12.x (REMOVED) --}}
@@theme('my-theme')
    {{-- Theme-specific content --}}
@@endtheme

{{-- Pollora 13.x --}}
@@if(app('theme.service')->hasTheme('my-theme'))
    {{-- Theme-specific content --}}
@@endif
@endboostsnippet

## Config-Based Post Type and Taxonomy Registration Removal

**Likelihood Of Impact: High**

Configuration file-based registration of post types and taxonomies via `config/post-types.php` and `config/taxonomies.php` has been **removed** in Pollora 13.4.

### Search Patterns

- Check if `config/post-types.php` exists
- Check if `config/taxonomies.php` exists
- Search for references to these config keys in code

### Migration

Convert each config entry to an attribute-based class:

@boostsnippet('Post Type Config to Attribute Migration', 'php')
// Pollora 12.x — config/post-types.php (REMOVED)
return [
    'book' => [
        'label' => 'Books',
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => true,
    ],
];

// Pollora 13.x — app/Cms/PostTypes/Book.php
namespace App\Cms\PostTypes;

use Pollora\Entity\PostType\Attributes\HasArchive;
use Pollora\Entity\PostType\Attributes\PostType;
use Pollora\Entity\PostType\Attributes\PubliclyQueryable;
use Pollora\Entity\PostType\Attributes\ShowInRest;
use Pollora\Entity\PostType\Attributes\Supports;

#[PostType('book')]
#[PubliclyQueryable]
#[HasArchive]
#[Supports(['title', 'editor', 'thumbnail'])]
#[ShowInRest]
class Book {}
@endboostsnippet

@boostsnippet('Taxonomy Config to Attribute Migration', 'php')
// Pollora 12.x — config/taxonomies.php (REMOVED)
return [
    'genre' => [
        'label' => 'Genres',
        'object_type' => ['book'],
        'hierarchical' => true,
        'show_in_rest' => true,
    ],
];

// Pollora 13.x — app/Cms/Taxonomies/BookGenre.php
namespace App\Cms\Taxonomies;

use Pollora\Entity\Taxonomy\Attributes\Hierarchical;
use Pollora\Entity\Taxonomy\Attributes\ShowInRest;
use Pollora\Entity\Taxonomy\Attributes\Taxonomy;

#[Taxonomy('genre', objectType: 'book')]
#[Hierarchical]
#[ShowInRest]
class BookGenre {}
@endboostsnippet

After creating the classes, **delete** `config/post-types.php` and `config/taxonomies.php`, then rebuild the discovery cache:

```bash
php artisan discovery:clear
php artisan discovery:run
```

## Route Model Namespace Change

**Likelihood Of Impact: Medium**

The Route model has been moved from the Domain layer to the Infrastructure layer in Pollora 13.4:

### Search Patterns

Search for `Pollora\Route\Domain\Models\Route` in all PHP files.

### Migration

@boostsnippet('Route Namespace Migration', 'php')
// Pollora 12.x
use Pollora\Route\Domain\Models\Route;

// Pollora 13.x
use Pollora\Route\Infrastructure\Models\Route;
@endboostsnippet

## CSRF Middleware Update (Laravel 13)

**Likelihood Of Impact: High**

Laravel 13 renames the CSRF middleware from `ValidateCsrfToken` / `VerifyCsrfToken` to `PreventRequestForgery`. Pollora projects must update both `bootstrap/app.php` and `config/sanctum.php`.

### Search Patterns

- `ValidateCsrfToken` or `VerifyCsrfToken` in PHP files
- `validateCsrfTokens` method calls in `bootstrap/app.php`

### Migration

@boostsnippet('CSRF bootstrap/app.php Migration', 'php')
// Pollora 12.x (Laravel 12)
$middleware->validateCsrfTokens(except: [
    '*'  // too permissive — avoid this
]);

// Pollora 13.x (Laravel 13)
$middleware->preventRequestForgery(except: [
    'cms/wp-login.php',
    'cms/wp-admin/*',
    'wp-login.php',
    'wp-admin/*',
    'wp-cron.php',
    'wp-json/*',
]);
@endboostsnippet

@boostsnippet('CSRF sanctum.php Migration', 'php')
// config/sanctum.php

// Pollora 12.x
'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,

// Pollora 13.x
'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
@endboostsnippet

**Important:** Do not use a wildcard `'*'` exception. Instead, exclude only WordPress-specific routes that use their own nonce system.

## New Features in Pollora 13.x

After upgrading, consider adopting these new features:

### Gutenberg Block System (v13.4)

Generate blocks with the new Artisan command:

```bash
php artisan pollora:make:block my-block
```

Creates a block with `block.json`, JSX/TSX entry, and CSS — built with Vite and Tailwind CSS v4.

### Admin Dashboard (v13.4)

A new admin page under **Tools > Pollora** displays framework status, discovered components, and module status. Also available via CLI:

```bash
php artisan pollora:status
php artisan pollora:status --json
```

### WordPress Events as Laravel Events (v13.0)

WordPress lifecycle events are now dispatched as Laravel events:

@boostsnippet('WordPress Events', 'php')
use Pollora\Events\WordPress\Post\PostPublished;

class SendNotification implements ShouldQueue
{
    public function handle(PostPublished $event): void
    {
        $post = $event->post; // WP_Post instance
    }
}
@endboostsnippet

Available event families: Post, Media, Taxonomy, User, Comment, Menu, Widget, Blog.

### Theme API Routes — Lightweight Mode (v13.0)

Define API routes in your theme that load WordPress without plugins for faster responses:

@boostsnippet('Theme API Routes', 'php')
// themes/my-theme/routes/api.php
Route::get('/products/search', ProductSearchController::class);

// config/wordpress.php
'api_plugins' => [],              // No plugins (fastest, ~100ms)
'api_plugins' => ['woocommerce'], // Selective loading
'api_plugins' => ['*'],           // All plugins (~1.3s)
@endboostsnippet

### Cache Control Enhancements (v13.4)

Configure cache headers and per-condition TTLs:

@boostsnippet('Cache Configuration', 'php')
// config/wordpress.php
'headers' => [
    'cache_max_age' => 3600,
],
'cache' => [
    'ttl' => [
        'is_front_page' => 600,
        'is_single' => 300,
    ],
],
@endboostsnippet

## Theme Vite Configuration — `wordpressThemeJson` Plugin

**Likelihood Of Impact: High**

Pollora 13.4 introduces a `ThemeJsonResolver` that reads a Vite-built `theme.json` from `public/build/theme/{slug}/assets/theme.json`. Without this, WordPress cannot resolve font paths declared in `theme.json` with `file:` protocol, causing **fonts not to load**.

### Required Changes

1. **Install `@roots/vite-plugin`** in your theme:

```bash
cd themes/your-theme && npm install -D @roots/vite-plugin
```

2. **Update `vite.config.js`** to add the `wordpressThemeJson` and `copy-theme-json` plugins:

@boostsnippet('Vite Config Update', 'js')
// Add import
import { wordpressThemeJson } from '@roots/vite-plugin';

// Add to plugins array (after laravel() plugin):
wordpressThemeJson({
    baseThemeJsonPath: './theme.json',
}),
{
    name: "copy-theme-json",
    apply: "build",
    async writeBundle(options) {
        const fs = await import('fs/promises');
        const src = path.join(options.dir, 'assets', 'theme.json');
        const dest = path.resolve(__dirname, 'theme.json');
        try {
            await fs.copyFile(src, dest);
            console.log('  ✓ theme.json copied to theme root');
        } catch {}
    },
},
@endboostsnippet

3. **Add font assets** to the Laravel Vite plugin config:

@boostsnippet('Vite Assets Config', 'js')
// In getThemeConfig() or laravel() config:
assets: [
    'resources/assets/images/**',
    'resources/assets/fonts/**',
],
@endboostsnippet

4. **Verify `theme.json` font paths** match the actual filesystem structure. The `src` paths must be relative to the theme root and point to existing files:

@boostsnippet('theme.json Font Paths', 'json')
// If fonts are in resources/assets/fonts/:
"src": ["file:./resources/assets/fonts/Poppins/Poppins-Regular.ttf"]

// NOT this (unless the fonts/ directory exists at theme root):
"src": ["file:./fonts/Poppins/Poppins-Regular.ttf"]
@endboostsnippet

5. **Rebuild theme assets** after making these changes:

```bash
cd themes/your-theme && npm run build
```

### Why This Matters

WordPress uses `wp_print_font_faces()` to generate `@font-face` rules from `theme.json`. It verifies that font files exist **physically on disk** before generating the CSS. If the `file:` paths don't resolve to real files relative to the theme directory, fonts are silently skipped — no error, just fallback to system fonts.

## Vite Hot File — Dev Server vs Compiled Assets

**Likelihood Of Impact: Medium (development workflow)**

When the Vite dev server is running, a hot file (e.g., `public/default.hot`) is created. If this file persists after stopping the dev server, all assets will fail to load (page appears unstyled or blank).

### Fix

Remove the hot file to use compiled assets:

```bash
rm public/*.hot
```

Then rebuild if needed:

```bash
cd themes/your-theme && npm run build
```

## Post-Upgrade Cleanup

After completing all changes and rebuilding assets, run these commands to clear all cached data:

```bash
php artisan optimize:clear
php artisan discovery:clear && php artisan discovery:run
wp transient delete --all
```

The transient flush is important because WordPress caches theme.json data, font paths, and other resolved paths in transients. Stale transients from the v12 installation can cause fonts, styles, or block settings to not update after the upgrade.

If using DDEV, prefix WP-CLI commands with `ddev exec` (e.g., `ddev exec wp transient delete --all`).

## Getting Help

If you encounter issues during the upgrade:

- Check the [Pollora changelog](https://github.com/pollora/framework/blob/main/CHANGELOG.md) for detailed release notes
- Run `php artisan pollora:status` (after upgrade) to verify framework health
- Use `php artisan discovery:clear && php artisan discovery:run` to reset the discovery cache
- Use `wp transient delete --all` to clear WordPress cached paths

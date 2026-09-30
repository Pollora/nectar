---
name: pollora-theming
description: Develop Pollora themes with Blade templates or as Full Site Editing block themes, Vite asset bundling, Tailwind CSS, and WordPress block editor integration.
---

# Pollora Theme Development

## When to use this skill
Use this skill when creating themes, working with Blade templates, configuring assets, or customizing the WordPress appearance layer in a Pollora project.

## Creating a Theme

Generate a new theme:

```bash
php artisan pollora:make:theme my-theme
```

This creates a complete theme at `themes/my-theme/`. It is activated when the site has no usable theme (a first install); a site with one keeps it unless asked otherwise — the question defaults to "no", so `--no-interaction` never replaces a working theme. `--activate` / `--no-activate` decide without asking. The command offers three templates:

| Template | Repository | What it is |
|---|---|---|
| `default` | `pollora/theme-default` | Blade starter: Vite, Tailwind CSS |
| `ecommerce` | `pollora/theme-apiary` | WooCommerce storefront, Blade + Alpine.js |
| `magazine` | `pollora/theme-buzz` | Full Site Editing block theme, see below |

Skip the prompt with `--repository=pollora/theme-buzz` (any `owner/repo` works).

## Theme Structure

```
themes/my-theme/
├── app/
│   └── Providers/            # Auto-discovered service providers
│       ├── AssetServiceProvider.php
│       └── MenuServiceProvider.php
├── config/
│   ├── gutenberg.php         # Block editor settings
│   ├── images.php            # Custom image sizes
│   ├── login.php             # Login screen (opt-in), see below
│   ├── menus.php             # Menu locations
│   ├── providers.php         # Additional service providers
│   ├── sidebars.php          # Widget areas
│   ├── supports.php          # Theme supports (title-tag, post-thumbnails, etc.)
│   └── templates.php         # Custom page templates
├── resources/
│   ├── assets/
│   │   ├── css/app.css       # Main stylesheet (Tailwind)
│   │   ├── js/app.js         # Main script
│   │   ├── fonts/
│   │   └── images/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php # Main layout
│       ├── blocks/           # Gutenberg blocks (see pollora-blocks)
│       ├── parts/            # Reusable partials
│       ├── home.blade.php
│       ├── page.blade.php
│       ├── single.blade.php
│       └── index.blade.php
├── functions.php             # pollora_register(ModuleType::Theme)
├── style.css                 # WordPress theme metadata (name, version, description)
├── theme.json                # Base block editor config; the build adds the @theme tokens
├── vite.config.js            # Vite build configuration
└── package.json
```

## Namespace Convention

Theme classes use `Theme\{ThemeName}\` namespace, auto-loaded from `app/` or `src/`:

```php
namespace Theme\MyTheme\Providers;

class AssetServiceProvider extends ServiceProvider {}
```

## Asset Management

### Registering Assets

Declare assets in service providers (not hookable classes):

```php
use Pollora\Support\Facades\Asset;

// In a service provider's boot() method
Asset::add('theme/styles', 'resources/assets/css/app.css')
    ->container('theme')
    ->useVite()
    ->toFrontend();

Asset::add('theme/scripts', 'resources/assets/js/app.js')
    ->container('theme')
    ->useVite()
    ->dependencies(['jquery'])
    ->loadInFooter()
    ->toFrontend();
```

### Asset URLs

```php
$logoUrl = Asset::url('assets/images/logo.png');
$cssUrl = Asset::url('assets/css/app.css')->from('theme');
```

WordPress's `get_theme_file_uri('resources/assets/images/logo.svg')` also returns the URL the build gave that file, so plugins and core code that call it work. A theme file outside the Vite build has no public URL.

### Vite Configuration

```js
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/assets/css/app.css', 'resources/assets/js/app.js'],
            buildDirectory: `build/theme/my-theme`,
            hotFile: `public/my-theme.hot`,
        }),
        tailwindcss(),
    ],
});
```

Build commands:
```bash
cd themes/my-theme && npm run dev    # Dev with HMR
cd themes/my-theme && npm run build  # Production
```

## Blade Templates

### Template Hierarchy

Pollora maps WordPress template names to Blade files:
- `page-about.blade.php` → `page.blade.php` → `singular.blade.php` → `index.blade.php`

### Sage Directives

```blade
@posts
    <h2>@title</h2>
    <div>@content</div>
    <a href="@permalink">Read more</a>
    <time>@published</time>
@endposts
```

### Layout Example

```blade
{{-- views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html @php(language_attributes())>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php(wp_head())
</head>
<body @php(body_class())>
    @yield('content')
    @php(wp_footer())
</body>
</html>
```

## Theme Configuration Files

### supports.php
```php
return [
    'title-tag',
    'post-thumbnails',
    'html5' => ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption'],
    'custom-logo' => ['height' => 100, 'width' => 400],
];
```

### menus.php
```php
return [
    'primary' => __('Primary Navigation', 'my-theme'),
    'footer' => __('Footer Navigation', 'my-theme'),
];
```

## Login Screen

Add `config/login.php` and the WordPress login screen wears the theme's design, read from `theme.json` (colours, radii, fonts). Without the file, WordPress's screen is unchanged.

```php
return [
    'enabled' => true,
    'logo' => [
        'source' => 'resources/assets/images/logo.svg', // path in the theme, URL or attachment id
        'width' => 220,
    ],
    // Only when the palette uses its own slug names:
    // 'tokens' => ['primary' => ['brand-600'], 'accent' => 'oklch(70% .2 30)'],
];
```

Roles resolve from common slugs (`primary`, `surface`, `foreground`, `outline`…). Modules and plugins can take over with the `pollora/login/palette`, `pollora/login/logo`, `pollora/login/styles` and `pollora/login/credit` filters.

## Block Themes (Full Site Editing)

A theme can be a WordPress block theme instead of a Blade one: its templates are `templates/*.html`, edited in the Site Editor. Pollora renders it with no configuration — when no Blade view answers, its fallback lets WordPress resolve the block template itself, with the right HTTP status (a 404 answers 404, `error404` on `<body>`). Generate one with the `magazine` template.

One rule decides where a file goes: **the theme root holds what WordPress reads itself; `resources/views/` holds Blade.**

```
themes/my-journal/
├── templates/*.html         # Block templates (index, single, page, archive, search, 404…) — WordPress reads them
├── parts/*.html             # Template parts (header, footer)
├── patterns/*.php           # Static patterns, registered by WordPress itself
├── resources/views/patterns/*.blade.php   # Patterns that need Laravel, registered by Pollora
├── theme.json               # The design system; the build adds the @theme colours
└── style.css
```

- `templates/` and `parts/` must sit at the theme root: WordPress has no setting to move them.
- A static pattern is a native WordPress one: `patterns/*.php`, block markup under a docblock header (`Title`, `Slug`, `Categories`, `Inserter`, `Block Types`). WordPress reads **only `.php`** in `patterns/` — an `.html` there is silently ignored. The same layout the Site Editor exports.
- A pattern that needs Laravel (config, a helper, a computed value) is a Blade view in `resources/views/patterns/*.blade.php`, its header in a Blade comment:

```blade
{{--
  Title: Colophon
  Slug: my-journal/colophon
  Categories: my-journal/patterns
  Inserter: false
--}}
```
- A template references a pattern with `<!-- wp:pattern {"slug":"my-journal/masthead"} /-->`: templates stay thin, the markup lives in patterns.
- WordPress caches a theme's `patterns/` list: a new file appears once the cache is cleared (`wp eval 'wp_get_theme()->delete_pattern_cache();'`) or at once with `WP_DEVELOPMENT_MODE=theme`.
- Assets work as in any Pollora theme (`Asset::add(...)->useVite()`); a Vite entry is enqueued as a script module, after WordPress's import map.
- Don't add `Route::wp()` routes for pages the block templates render: a route answers first and bypasses them.

## Debugging Templates

With `WP_DEBUG` on, each page rendered through the template hierarchy says which template answered:

```bash
curl -s https://site.test/some-page | grep pollora:template
# <!-- pollora:template="page" path="themes/my-theme/resources/views/page.blade.php" -->
```

Responses from `Route::wp()` and Laravel routes carry no marker.

In a block theme the marker always reads `template="template-canvas"` (WordPress renders every block template through `wp-includes/template-canvas.php`): tell templates apart by the `<body>` classes instead (`single-post`, `search-results`, `error404`…).

## Important Notes

- **Never create WordPress PHP template files** — use Blade exclusively. The exception is a block theme, whose `templates/*.html`, `parts/*.html` and `patterns/*.php` WordPress resolves itself (see Block Themes)
- Theme providers in `app/Providers/` are auto-discovered
- Design tokens (colours, font sizes, fonts, radii) go in the `@theme static` block of `app.css`, with concrete values; `wordpressThemeJson` writes them into the built `theme.json` the editor reads. Never `@import "tailwindcss" theme(static)` (it puts Tailwind's whole palette in the editor), never `var(--wp--preset--…)` inside `@theme` (a cycle once copied)
- The root `theme.json` is the base: layout, spacing, `fontFace`, editor settings. A slug defined there wins over `@theme`; a whole family can be taken out of the generation with `disableTailwindColors` / `disableTailwindFontSizes` / `disableTailwindFonts` / `disableTailwindBorderRadius`
- Tailwind CSS v4 is auto-detected — no configuration file needed
- Build output goes to `public/build/theme/{theme-name}/`
- Use `Asset` facade in service providers, not in hookable classes
---
name: pollora-theming
description: Develop Pollora themes with Blade templates, Vite asset bundling, Tailwind CSS, and WordPress block editor integration.
---

# Pollora Theme Development

## When to use this skill
Use this skill when creating themes, working with Blade templates, configuring assets, or customizing the WordPress appearance layer in a Pollora project.

## Creating a Theme

Generate a new theme:

```bash
php artisan pollora:make-theme my-theme
```

This creates a complete theme at `themes/my-theme/` and auto-activates it.

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
│       ├── parts/            # Reusable partials
│       ├── home.blade.php
│       ├── page.blade.php
│       ├── single.blade.php
│       └── index.blade.php
├── functions.php             # Theme registration entry point
├── style.css                 # WordPress theme metadata (name, version, description)
├── theme.json                # Block editor / Full Site Editing config
├── vite.config.js            # Vite build configuration
├── tailwind.config.js        # Tailwind (v3) or not needed (v4 auto-detection)
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

## Important Notes

- **Never create WordPress PHP template files** — use Blade exclusively
- Theme providers in `app/Providers/` are auto-discovered
- The `theme.json` file controls block editor settings (colors, fonts, spacing)
- Tailwind CSS v4 is auto-detected — no configuration file needed
- Build output goes to `public/build/theme/{theme-name}/`
- Use `Asset` facade in service providers, not in hookable classes
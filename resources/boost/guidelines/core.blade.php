## Pollora Framework

Pollora is a Laravel & WordPress integration framework. It replaces WordPress's frontend templating with Laravel's Blade engine while keeping WordPress's full backend (admin, database, plugins). All code follows Laravel conventions first.

### Architecture

- **Laravel-first routing**: Custom routes in `routes/web.php` take priority over WordPress template hierarchy
- **Blade templates**: No PHP template files — use `.blade.php` views exclusively
- **DDD structure**: Framework modules use Domain/Application/Infrastructure layers
- **Auto-discovery**: Components are registered automatically via PHP 8 attributes — no manual `register_post_type()` or `add_action()` calls needed

### WordPress Routing

Use `Route::wp()` to bind WordPress template conditions to Laravel controllers or closures:

@verbatim
<code-snippet name="WordPress routes" lang="php">
// routes/web.php
Route::wp('home', [HomeController::class, 'index']);
Route::wp('single', [PostController::class, 'show']);
Route::wp('page', 'contact', [ContactController::class, 'index']);
Route::wp('archive', fn () => view('archive'));
Route::wp('404', fn () => response()->view('errors.404', [], 404));
</code-snippet>
@endverbatim

A catch-all `{any}` route provides WordPress template hierarchy fallback — if no explicit route matches, Pollora resolves Blade templates following WordPress naming conventions (`page-about.blade.php` → `page.blade.php` → `singular.blade.php` → `index.blade.php`).

### PHP 8 Attributes for Registration

Pollora uses attributes instead of WordPress function calls:

@verbatim
<code-snippet name="Attribute-based registration" lang="php">
// Post types
#[PostType('book')]
#[PubliclyQueryable]
#[HasArchive]
#[Supports(['title', 'editor', 'thumbnail'])]
#[ShowInRest]
class Book {}

// Taxonomies
#[Taxonomy('genre', objectType: 'book')]
#[Hierarchical]
#[ShowInRest]
class BookGenre {}

// Hooks
#[Action('init', priority: 20)]
public function onInit(): void {}

#[Filter('the_content')]
public function filterContent(string $content): string {}

// REST API endpoints
#[WpRestRoute(namespace: 'app/v1', route: 'items')]
class ItemAPI {}

// Scheduled tasks
#[Schedule(Every::DAY)]
public function dailyCleanup(): void {}
</code-snippet>
@endverbatim

### Theme Development

Themes are generated with `php artisan pollora:make-theme my-theme`. Theme structure:

```
themes/my-theme/
├── app/Providers/        # Auto-discovered service providers
├── config/               # menus.php, supports.php, gutenberg.php, etc.
├── resources/
│   ├── assets/           # JS, CSS, fonts, images
│   └── views/            # Blade templates
├── functions.php         # Theme registration entry point
├── style.css             # WordPress theme metadata
├── theme.json            # Block editor settings
└── vite.config.js        # Vite build config
```

Themes use Vite for asset bundling with HMR, Tailwind CSS v4, and the `Asset` facade for script/style registration. Theme classes use the `Theme\{ThemeName}\` namespace.

### Key Conventions

- **Never call WordPress registration functions directly** — use attributes and discovery
- **Use Blade directives** from Sage Directives: `@posts`, `@title`, `@content`, `@permalink`, `@published`
- **WordPress objects** (`WP_Post`, `WP_Query`, `WP_User`) are auto-injected via type hints in controller methods
- **Facades**: `Action`, `Filter`, `Query`, `Asset`, `Theme`, `PostType`, `Taxonomy`, `Option`, `Loop`, `Ajax`
- **CSRF**: WordPress endpoints are excluded from Laravel CSRF — WordPress uses its own nonce system
- **Modules**: Use `nwidart/laravel-modules` for large projects — discovery works inside modules automatically

### Available Artisan Commands

- `pollora:install` — Full project installation
- `pollora:make-theme` — Generate a new theme
- `pollora:make-block` — Generate a Gutenberg block
- `pollora:make-post-type` — Generate a post type class
- `pollora:make-action` / `pollora:make-filter` — Generate hook classes
- `discovery:run` / `discovery:clear` — Manage component discovery cache
- `pollora:status` — Show framework status

### Pollora Nectar MCP Tools

When available, use the `pollora-nectar` MCP server for live introspection:
- `pollora_status` — Framework health and versions
- `wordpress_info` — WordPress version, plugins, theme
- `post_types_info` — Registered custom post types
- `taxonomies_info` — Registered taxonomies
- `registered_hooks` — Discovered hooks (actions/filters)
- `active_theme_info` — Active theme details
- `discovered_components` — All auto-discovered components
- `wordpress_routes` — Route::wp() routes and conditions
- `modules_info` — Installed Laravel Modules
- `wp_option` — Read WordPress options
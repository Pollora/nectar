## Pollora Framework

Pollora is a Laravel & WordPress integration framework. It replaces WordPress's frontend templating with Laravel's Blade engine while keeping WordPress's full backend (admin, database, plugins). All code follows Laravel conventions first.

### Architecture

- **Laravel-first routing**: Custom routes in `routes/web.php` take priority over WordPress template hierarchy
- **Blade templates**: No PHP template files — use `.blade.php` views exclusively. The one exception is a block theme (Full Site Editing), whose `templates/*.html`, `parts/*.html` and `patterns/*.php` WordPress resolves itself (`pollora:make:theme` template `magazine`)
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

// AJAX handlers — logged-in users only unless access says otherwise
#[Ajax('load_more', access: AjaxAccess::ALL)]
public function loadMore(): void {}

// WordPress Abilities API (WP 6.9+) — a class implementing AbilityHandler
#[Ability(name: 'acme/create-post', description: 'Creates a post.', category: 'acme-content')]
final class CreatePost implements AbilityHandler {}

// Keep a class out of discovery entirely (or all but some: except: [...])
#[SkipDiscovery]
class InternalHelper {}
</code-snippet>
@endverbatim

### Theme Development

Themes are generated with `php artisan pollora:make:theme my-theme`. Theme structure:

```
themes/my-theme/
├── app/Providers/        # Auto-discovered service providers
├── config/               # menus.php, supports.php, gutenberg.php, etc.
├── resources/
│   ├── assets/           # JS, CSS, fonts, images
│   └── views/            # Blade templates
│       └── blocks/       # Gutenberg blocks, registered automatically
├── functions.php         # pollora_register(ModuleType::Theme)
├── style.css             # WordPress theme metadata
├── theme.json            # Base editor settings; the build adds the @theme tokens
└── vite.config.js        # Vite build config
```

Themes use Vite for asset bundling with HMR, Tailwind CSS v4, and the `Asset` facade for script/style registration. Theme classes use the `Theme\{ThemeName}\` namespace. A plugin registers with `pollora_register(ModuleType::Plugin, 'my-plugin', __DIR__)` (`use Pollora\Modules\Domain\Enums\ModuleType;`).

Design tokens (colours, font sizes, fonts, radii) live in the `@theme static` block of `app.css` with concrete values; the build writes them into the `theme.json` the editor reads. `get_theme_file_uri()` returns the built URL of a theme asset.

### Key Conventions

- **Never call WordPress registration functions directly** — use attributes and discovery
- **Use Blade directives** from Sage Directives: `@posts`, `@title`, `@content`, `@permalink`, `@published`
- **WordPress objects** (`WP_Post`, `WP_Query`, `WP_User`) are auto-injected via type hints in controller methods
- **Facades**: `Action`, `Filter`, `Ajax`, `Asset`, `Theme`, `PostType`, `Taxonomy`, `Option`, `Ability`, `PostQuery`, `MetaQuery`, `TaxQuery`, `Mail`, `Constant`. There is no `Loop` or `Query` facade: use Sage Directives in Blade, WordPress functions in PHP
- **Translations**: `__('Text', 'my-domain')` goes to WordPress's catalogues; `__('key', ['name' => $x])` goes to Laravel; `__('Text')` tries Laravel, then WordPress's `default` domain
- **Blocks**: live in `resources/views/blocks/{slug}`, render with `render.blade.php` by default, and are registered by Pollora — never write a `BlocksServiceProvider`; `<InnerBlocks />` in `render.blade.php` marks where inner blocks go, edited in place in the editor (see the `pollora-blocks` skill)
- **Which template answered?** With `WP_DEBUG` on, every page carries `<!-- pollora:template="single" path="themes/x/resources/views/single.blade.php" -->` (hierarchy responses only, not `Route::wp()` or Laravel routes)
- **CSRF**: WordPress endpoints are excluded from Laravel CSRF — WordPress uses its own nonce system
- **Modules**: Use `nwidart/laravel-modules` for large projects — discovery works inside modules automatically

### Available Artisan Commands

- `pollora:install` — Full project installation (`--theme` to pick the generated theme; runs without interaction)
- `pollora:env:setup` — Install and configure WordPress
- `pollora:make:theme` / `pollora:theme:delete` / `pollora:theme:status` — Themes
- `pollora:make:plugin` / `pollora:plugin:list` / `pollora:plugin:status` — Plugins
- `pollora:make:block` — Generate a Gutenberg block (dynamic Blade by default, `--static` for `save.jsx`)
- `pollora:make:post-type` / `pollora:make:taxonomy` — Generate post type and taxonomy classes
- `pollora:make:action` / `pollora:make:filter` / `pollora:make:hook` — Generate hook classes
- `pollora:make:model` / `pollora:make:wp-cli` — Generate an Eloquent model or a WP-CLI command class
- `discovery:run` / `discovery:clear` — Manage component discovery cache
- `pollora:status` — Show framework status

Commands use the colon convention since v13.32; the former dashed names (`pollora:make-theme`…) still work as aliases.

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
{{-- Boost reads every file under resources/boost/guidelines for a package
     outside Laravel's own, subdirectories included, so one file per framework
     major would all land in the project. This one picks by the installed major.
     A branch without an alias (dev-develop) reads as 0 and gets the 13.x text. --}}
@php
    $polloraMajor ??= (int) ltrim((string) \Composer\InstalledVersions::getVersion('pollora/framework'), 'vV');
@endphp
@if ($polloraMajor === 12)
@verbatim
## Pollora 12.x Specifics

Pollora 12.x targets **Laravel 12.x** and **PHP 8.2+**.

### Laravel 12 Compatibility

- Uses `illuminate/*` packages at `^12.54`
- Supports Pest 3.x for testing
- Some framework dependencies use `dev-main` stability

### WordPress Integration

- WordPress content directory location depends on project configuration (commonly `public/content/` or `public/wp-content/`)
- WordPress core at `public/wp/` or `public/cms/` depending on Pollora config

### Loop Facade (Available in v12)

The `Loop` facade provides access to WordPress loop data inside Blade templates:

<code-snippet name="Loop facade usage" lang="php">
use Pollora\Support\Facades\Loop;

// In controllers or views
Loop::title();     // Current post title
Loop::content();   // Current post content
Loop::excerpt();   // Current post excerpt
Loop::thumbnail(); // Current post thumbnail
Loop::id();        // Current post ID
</code-snippet>

**Note:** The `Loop` facade is deprecated and will be removed in Pollora 13.x. Prefer Sage Directives (`@title`, `@content`, `@excerpt`, `@permalink`, `@published`) for new code.

### @theme Blade Directive (Available in v12)

The `@theme` directive checks for the active theme:

<code-snippet name="@theme directive" lang="blade">
@theme('my-theme')
    {{-- Rendered only if 'my-theme' is active --}}
@endtheme
</code-snippet>

**Note:** This directive will be removed in Pollora 13.x due to conflicts with Tailwind CSS v4's `@theme` at-rule. Use `app('theme.service')->hasTheme($name)` for new code.

### Config-Based Registration (Available in v12)

Post types and taxonomies can be registered via configuration files:

<code-snippet name="Config-based post type registration" lang="php">
// config/post-types.php
return [
    'book' => [
        'label' => 'Books',
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => true,
    ],
];

// config/taxonomies.php
return [
    'genre' => [
        'label' => 'Genres',
        'object_type' => ['book'],
        'hierarchical' => true,
        'show_in_rest' => true,
    ],
];
</code-snippet>

**Note:** Config-based registration is deprecated and will be removed in Pollora 13.x. Prefer PHP 8 attribute-based classes (`#[PostType]`, `#[Taxonomy]`) for new code.

### Route Model Namespace

In Pollora 12.x, the Route model lives at:

<code-snippet name="Route model import" lang="php">
use Pollora\Route\Domain\Models\Route;
</code-snippet>

### Discovery System

The discovery system uses `spatie/php-structure-discoverer` for attribute scanning. All discovered components are cached — run `php artisan discovery:clear` after adding new attribute-decorated classes during development.
@endverbatim
@else
@verbatim
## Pollora 13.x Specifics

Pollora 13.x targets **Laravel 13.x** and **PHP 8.3+**. Since v13.32 the framework version tracks the Laravel release it targets.

### Laravel 13 Compatibility

- Requires `illuminate/*` `^13.34`; v13.34.0 is the first stable release on it
- Supports Pest 3.x for testing
- PHPStan level 5 with WordPress and Laravel extensions
- Rector with Laravel-specific rules

### WordPress 7 Integration

- WordPress 7.x (`johnpbloch/wordpress` `^7.0`) installed at `public/cms/`
- Content directory at `public/content/`
- Full Site Editing support via `theme.json`, generated from the theme's `@theme` tokens
- Gutenberg blocks with Vite + JSX/TSX + Tailwind CSS v4, rendered with Blade
- Abilities API through `pollora/abilities` (WordPress 6.9+)

### Extracted Packages

Some modules now live in their own packages, installed with the framework: `pollora/hook` (hook domain and adapters), `pollora/option`, `pollora/ajax`, `pollora/abilities`.

### Discovery System Enhancements

The discovery system in v13.x uses `spatie/php-structure-discoverer` for attribute scanning. All discovered components are cached — run `php artisan discovery:clear` after adding new attribute-decorated classes during development.

### Template Hierarchy

The template hierarchy resolver supports extensible handlers:

<code-snippet name="Custom template handler" lang="php">
$templateHierarchy->registerTemplateHandler('product_on_sale', function($post) {
    return ['product-on-sale.blade.php'];
});
</code-snippet>

### Theme API Routes (Lightweight Mode)

Themes can define API routes that load WordPress without plugins for faster responses (~100ms vs ~1.3s):

<code-snippet name="Theme API routes" lang="php">
// themes/my-theme/routes/api.php
Route::get('/products/search', ProductSearchController::class);
// → GET /api/products/search

// config/wordpress.php — control plugin loading
'api_plugins' => [],                    // No plugins (fastest)
'api_plugins' => ['woocommerce'],       // Selective
'api_plugins' => ['*'],                 // All plugins
</code-snippet>

### Events System

WordPress events are automatically dispatched as Laravel events:

<code-snippet name="WordPress events" lang="php">
use Pollora\Events\WordPress\Post\PostPublished;

class SendNotification implements ShouldQueue
{
    public function handle(PostPublished $event): void
    {
        $post = $event->post; // WP_Post instance
    }
}
</code-snippet>

Available event families: Post, Media, Taxonomy, User, Comment, Menu, Widget, Blog, plus plugin-specific events for WooCommerce, Yoast SEO, Gravity Forms.
@endverbatim
@endif

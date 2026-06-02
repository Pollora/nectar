## Pollora 13.x Specifics

Pollora 13.x targets **Laravel 13.x** and **PHP 8.3+**.

### Laravel 13 Compatibility

- Uses `illuminate/*` packages at `^13.0`
- Supports Pest 3.x for testing
- PHPStan level 5 with WordPress and Laravel extensions
- Rector with Laravel-specific rules

### WordPress 6.9 Integration

- WordPress installed at `public/cms/`
- Content directory at `public/content/`
- Full Site Editing support via `theme.json`
- Gutenberg blocks with Vite + JSX/TSX + Tailwind CSS v4

### Discovery System Enhancements

The discovery system in v13.x uses `spatie/php-structure-discoverer` for attribute scanning. All discovered components are cached — run `php artisan discovery:clear` after adding new attribute-decorated classes during development.

### Template Hierarchy

The template hierarchy resolver supports extensible handlers:

@verbatim
<code-snippet name="Custom template handler" lang="php">
$templateHierarchy->registerTemplateHandler('product_on_sale', function($post) {
    return ['product-on-sale.blade.php'];
});
</code-snippet>
@endverbatim

### Theme API Routes (Lightweight Mode)

Themes can define API routes that load WordPress without plugins for faster responses (~100ms vs ~1.3s):

@verbatim
<code-snippet name="Theme API routes" lang="php">
// themes/my-theme/routes/api.php
Route::get('/products/search', ProductSearchController::class);
// → GET /api/products/search

// config/wordpress.php — control plugin loading
'api_plugins' => [],                    // No plugins (fastest)
'api_plugins' => ['woocommerce'],       // Selective
'api_plugins' => ['*'],                 // All plugins
</code-snippet>
@endverbatim

### Events System

WordPress events are automatically dispatched as Laravel events:

@verbatim
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
@endverbatim

Available event families: Post, Media, Taxonomy, User, Comment, Menu, Widget, Blog, plus plugin-specific events for WooCommerce, Yoast SEO, Gravity Forms.
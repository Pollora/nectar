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

@verbatim
<code-snippet name="Loop facade usage" lang="php">
use Pollora\Support\Facades\Loop;

// In controllers or views
Loop::title();     // Current post title
Loop::content();   // Current post content
Loop::excerpt();   // Current post excerpt
Loop::thumbnail(); // Current post thumbnail
Loop::id();        // Current post ID
</code-snippet>
@endverbatim

**Note:** The `Loop` facade is deprecated and will be removed in Pollora 13.x. Prefer Sage Directives (`@@title`, `@@content`, `@@excerpt`, `@@permalink`, `@@published`) for new code.

### @@theme Blade Directive (Available in v12)

The `@@theme` directive checks for the active theme:

@verbatim
<code-snippet name="@theme directive" lang="blade">
@@theme('my-theme')
    {{-- Rendered only if 'my-theme' is active --}}
@@endtheme
</code-snippet>
@endverbatim

**Note:** This directive will be removed in Pollora 13.x due to conflicts with Tailwind CSS v4's `@@theme` at-rule. Use `app('theme.service')->hasTheme($name)` for new code.

### Config-Based Registration (Available in v12)

Post types and taxonomies can be registered via configuration files:

@verbatim
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
@endverbatim

**Note:** Config-based registration is deprecated and will be removed in Pollora 13.x. Prefer PHP 8 attribute-based classes (`#[PostType]`, `#[Taxonomy]`) for new code.

### Route Model Namespace

In Pollora 12.x, the Route model lives at:

@verbatim
<code-snippet name="Route model import" lang="php">
use Pollora\Route\Domain\Models\Route;
</code-snippet>
@endverbatim

### Discovery System

The discovery system uses `spatie/php-structure-discoverer` for attribute scanning. All discovered components are cached — run `php artisan discovery:clear` after adding new attribute-decorated classes during development.

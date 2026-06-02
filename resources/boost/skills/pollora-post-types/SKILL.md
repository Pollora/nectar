---
name: pollora-post-types
description: Create and configure WordPress custom post types using Pollora PHP 8 attributes and the discovery system.
---

# Pollora Post Types Development

## When to use this skill
Use this skill when creating, modifying, or configuring WordPress custom post types in a Pollora project.

## Creating a Post Type

Post types are PHP classes decorated with attributes. Place them where the discovery system scans — typically `app/Cms/PostTypes/` in themes or `app/Models/` in modules.

### Basic Post Type

```php
<?php

namespace Theme\MyTheme\Cms\PostTypes;

use Pollora\Attributes\PostType\PostType;
use Pollora\Attributes\PostType\PubliclyQueryable;
use Pollora\Attributes\PostType\HasArchive;
use Pollora\Attributes\PostType\Supports;
use Pollora\Attributes\PostType\MenuIcon;
use Pollora\Attributes\PostType\ShowInRest;

#[PostType('book')]
#[PubliclyQueryable]
#[HasArchive]
#[Supports(['title', 'editor', 'thumbnail', 'excerpt'])]
#[MenuIcon('dashicons-book')]
#[ShowInRest]
class Book
{
}
```

### Post Type with Custom Configuration

Use `configuring()` for dynamic configuration and `withArgs()` for additional arguments:

```php
#[PostType('book')]
#[PubliclyQueryable]
#[HasArchive]
#[Supports(['title', 'editor', 'thumbnail'])]
#[ShowInRest]
class Book
{
    public function configuring(\Pollora\Entity\Domain\Model\PostType $postType): void
    {
        $postType->labels([
            'name' => __('Books', 'my-theme'),
            'singular_name' => __('Book', 'my-theme'),
            'add_new' => __('Add New Book', 'my-theme'),
        ]);
        $postType->rewrite(['slug' => 'library/books']);
    }

    public function withArgs(): array
    {
        return [
            'description' => 'Custom book post type for the library',
        ];
    }
}
```

### Available Attributes

| Attribute | Purpose | Example |
|-----------|---------|---------|
| `#[PostType('slug')]` | Register a post type | `#[PostType('book')]` |
| `#[PubliclyQueryable]` | Make queryable on frontend | `#[PubliclyQueryable]` or `#[PubliclyQueryable(false)]` |
| `#[PublicPostType]` | Shorthand for public + queryable | `#[PublicPostType]` |
| `#[HasArchive]` | Enable archive page | `#[HasArchive]` or `#[HasArchive('books')]` |
| `#[Supports([...])]` | Editor features | `#[Supports(['title', 'editor', 'thumbnail'])]` |
| `#[Hierarchical]` | Page-like hierarchy | `#[Hierarchical]` |
| `#[ShowInRest]` | Enable block editor + REST API | `#[ShowInRest]` |
| `#[MenuIcon('..')]` | Admin menu icon | `#[MenuIcon('dashicons-book')]` |
| `#[MenuPosition(n)]` | Admin menu position | `#[MenuPosition(25)]` |
| `#[CapabilityType('..')]` | Custom capability type | `#[CapabilityType('book')]` |
| `#[MapMetaCap]` | Map meta capabilities | `#[MapMetaCap]` |
| `#[Labels(...)]` | Override specific labels | `#[Labels(addNew: 'New Book')]` |
| `#[Description('..')]` | Post type description | `#[Description('Library books')]` |
| `#[Template([...])]` | Default block template | `#[Template([['core/heading'], ['core/paragraph']])]` |
| `#[TemplateLock('..')]` | Lock template | `#[TemplateLock('all')]` |
| `#[AdminCols([...])]` | Custom admin columns | See admin columns section |
| `#[Priority(n)]` | Registration order | `#[Priority(10)]` |

### Admin Columns

```php
#[PostType('book')]
#[AdminCols([
    'title' => ['title_cb' => fn() => 'Book Title'],
    'author' => ['taxonomy' => 'author'],
    'genre' => ['taxonomy' => 'genre'],
    'published' => ['meta_key' => 'published_date', 'date_format' => 'd/m/Y'],
])]
class Book {}
```

### Routing for Post Types

```php
// routes/web.php
Route::wp('single', [BookController::class, 'show']);        // All single posts
Route::wp('archive', [BookController::class, 'index']);      // All archives

// The controller receives WP_Post via type-hint
public function show(\WP_Post $post): \Illuminate\View\View
{
    return view('book.show', compact('post'));
}
```

### Important Notes

- **No manual registration needed** — the discovery system finds and registers post types automatically
- Run `php artisan discovery:clear` after creating a new post type class during development
- Labels are auto-generated from the class name if not specified
- The slug is derived from the `#[PostType('slug')]` attribute parameter
- Generate a post type with `php artisan pollora:make-post-type`
---
name: pollora-taxonomies
description: Create and configure WordPress custom taxonomies using Pollora PHP 8 attributes and the discovery system.
---

# Pollora Taxonomies Development

## When to use this skill
Use this skill when creating, modifying, or configuring WordPress custom taxonomies in a Pollora project.

## Creating a Taxonomy

Taxonomies are PHP classes with attributes. Place them in `app/Cms/Taxonomies/` in themes or `app/Models/` in modules.

### Basic Taxonomy

```php
<?php

namespace Theme\MyTheme\Cms\Taxonomies;

use Pollora\Attributes\Taxonomy\Taxonomy;
use Pollora\Attributes\Taxonomy\Hierarchical;
use Pollora\Attributes\PostType\ShowInRest;

#[Taxonomy('genre', objectType: 'book')]
#[Hierarchical]
#[ShowInRest]
class BookGenre
{
}
```

### Taxonomy with Custom Configuration

```php
#[Taxonomy('genre', objectType: 'book')]
#[Hierarchical]
#[ShowInRest]
class BookGenre
{
    public function configuring(\Pollora\Entity\Domain\Model\Taxonomy $taxonomy): void
    {
        $taxonomy->labels([
            'name' => __('Genres', 'my-theme'),
            'singular_name' => __('Genre', 'my-theme'),
            'search_items' => __('Search Genres', 'my-theme'),
        ]);
        $taxonomy->rewrite(['slug' => 'genres']);
    }

    public function withArgs(): array
    {
        return [
            'description' => 'Book genres for the library',
        ];
    }
}
```

### Multiple Object Types

Associate a taxonomy with multiple post types:

```php
#[Taxonomy('genre', objectType: ['book', 'audiobook'])]
#[Hierarchical]
#[ShowInRest]
class Genre {}
```

### Available Attributes

| Attribute | Purpose | Example |
|-----------|---------|---------|
| `#[Taxonomy('slug', objectType: '...')]` | Register taxonomy | `#[Taxonomy('genre', objectType: 'book')]` |
| `#[Hierarchical]` | Category-like (vs tag-like) | `#[Hierarchical]` |
| `#[ShowInRest]` | Enable in block editor + REST API | `#[ShowInRest]` |
| `#[Labels(...)]` | Override specific labels | `#[Labels(addNew: 'Add Genre')]` |
| `#[AdminCols([...])]` | Custom admin columns | `#[AdminCols([...])]` |
| `#[Priority(n)]` | Registration order | `#[Priority(10)]` |

### Tag-like (Non-Hierarchical) Taxonomy

```php
#[Taxonomy('tag', objectType: 'book')]
#[ShowInRest]
class BookTag
{
    // Non-hierarchical by default (tag-like behavior)
}
```

### Important Notes

- **Auto-discovered** — no manual `register_taxonomy()` calls needed
- Run `php artisan discovery:clear` after creating a new taxonomy class
- The `objectType` parameter links the taxonomy to one or more post types
- Labels are auto-generated from the class name if not specified
- Use `configuring()` for translatable labels and custom rewrite rules
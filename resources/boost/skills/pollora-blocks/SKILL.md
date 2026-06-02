---
name: pollora-blocks
description: Create Gutenberg blocks in Pollora themes with Vite, JSX/TSX, and Tailwind CSS integration.
---

# Pollora Block Development

## When to use this skill
Use this skill when creating, configuring, or customizing WordPress Gutenberg blocks within a Pollora theme.

## Generating a Block

```bash
php artisan pollora:make-block hero-banner --theme --dynamic
```

Options:
- `--theme` — Create in the active theme's `resources/blocks/` directory
- `--dynamic` — Include a `render.php` for server-side rendering

## Block Structure

```
resources/blocks/hero-banner/
├── block.json           # WordPress block metadata
├── index.jsx            # Entry point & registration
├── edit.jsx             # Editor component (what authors see)
├── save.jsx             # Static save (or null for dynamic blocks)
├── render.php           # Server-side render (dynamic blocks only)
├── editor.css           # Editor-only styles
├── style.css            # Shared frontend + editor styles
└── view.js              # Frontend-only interactive script
```

## block.json

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "my-theme/hero-banner",
    "version": "1.0.0",
    "title": "Hero Banner",
    "category": "theme",
    "icon": "cover-image",
    "description": "A full-width hero banner with heading and CTA.",
    "supports": {
        "html": false,
        "align": ["wide", "full"]
    },
    "attributes": {
        "heading": { "type": "string", "default": "" },
        "ctaText": { "type": "string", "default": "Learn More" },
        "ctaUrl": { "type": "string", "default": "#" }
    },
    "textdomain": "my-theme",
    "editorScript": "file:./index.jsx",
    "editorStyle": "file:./editor.css",
    "style": "file:./style.css",
    "viewScript": "file:./view.js",
    "render": "file:./render.php"
}
```

## Editor Component (edit.jsx)

```jsx
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { TextControl } from '@wordpress/components';

export default function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();

    return (
        <div {...blockProps}>
            <RichText
                tagName="h1"
                value={attributes.heading}
                onChange={(heading) => setAttributes({ heading })}
                placeholder="Enter heading..."
            />
            <TextControl
                label="CTA Text"
                value={attributes.ctaText}
                onChange={(ctaText) => setAttributes({ ctaText })}
            />
        </div>
    );
}
```

## Dynamic Render (render.php)

```php
<?php
/**
 * @var array $attributes Block attributes.
 * @var string $content Block content.
 * @var WP_Block $block Block instance.
 */
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
    <h1><?php echo esc_html($attributes['heading']); ?></h1>
    <a href="<?php echo esc_url($attributes['ctaUrl']); ?>" class="btn">
        <?php echo esc_html($attributes['ctaText']); ?>
    </a>
</div>
```

## Registering Blocks

In a service provider:

```php
use Pollora\Block\Infrastructure\Services\BlockRegistrar;

public function boot(BlockRegistrar $registrar): void
{
    $registrar->registerDirectory(
        directory: dirname(__DIR__, 2) . '/resources/blocks',
        containerName: 'theme',
    );
}
```

## Tailwind CSS in Blocks

### Frontend + Editor Styles (style.css)

```css
@import "tailwindcss" source(".");

.wp-block-my-theme-hero-banner {
    @apply relative py-24 px-8 rounded-xl overflow-hidden;
    background: linear-gradient(135deg, theme(--color-indigo-950) 0%, theme(--color-violet-900) 100%);
}
```

### Editor-Only Styles (editor.css)

```css
@reference "tailwindcss";

.wp-block-my-theme-hero-banner {
    @apply border-2 border-dashed min-h-[300px];
}
```

## Vite Configuration for Blocks

```js
import { globSync } from 'fs';
import path from 'path';

const blockEntries = globSync('./resources/blocks/*/{index,view}.{js,jsx,ts,tsx}')
    .concat(globSync('./resources/blocks/*/{editor,style}.css'))
    .reduce((acc, file) => {
        const slug = path.basename(path.dirname(file));
        const name = path.basename(file, path.extname(file));
        acc[`blocks/${slug}/${name}`] = file;
        return acc;
    }, {});
```

## Important Notes

- Block names follow the pattern `{theme-slug}/{block-slug}` (e.g., `my-theme/hero-banner`)
- Use `@import "tailwindcss" source(".")` in `style.css` for full Tailwind support
- Use `@reference "tailwindcss"` in `editor.css` for editor-only Tailwind utilities
- Dynamic blocks use `render.php` and return `null` from `save.jsx`
- Vite auto-discovers block assets — no manual entry configuration needed
- The `BlockRegistrar` handles WordPress `register_block_type()` automatically
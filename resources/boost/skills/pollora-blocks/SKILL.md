---
name: pollora-blocks
description: Create Gutenberg blocks in Pollora themes, plugins and modules with Vite, JSX/TSX, Blade rendering and Tailwind CSS.
---

# Pollora Block Development

## When to use this skill
Use this skill when creating, configuring, or customizing WordPress Gutenberg blocks within a Pollora theme, plugin or module.

## Generating a Block

```bash
php artisan pollora:make:block hero-banner --theme
```

Options:
- `--theme[=NAME]` — Create in a theme (default: the active theme)
- `--plugin=NAME` — Create in a plugin
- `--static` — A static block saved in `post_content` (`save.jsx`, no `render.blade.php`)
- `--inner-blocks` — Add InnerBlocks support
- `--namespace=NS`, `--title=TITLE`, `--category=CAT`, `--icon=ICON`, `--no-view-script`, `--force`
- `--dynamic` is deprecated: blocks are dynamic by default

On the first block, the command patches `vite.config.js` (block entries, `wordpressPlugin()`, Blade-only full reloads) and adds the npm dependencies. It refuses a theme or plugin with no `package.json` or no `vite.config.js`.

## Block Structure

```
resources/views/blocks/hero-banner/
├── block.json           # WordPress block metadata
├── render.blade.php     # Server-side render (default)
├── index.jsx            # Entry point & registration
├── edit.jsx             # Editor component — shows what the page will show
├── save.jsx             # Static blocks only (--static)
├── editor.css           # Editor-only styles
├── style.css            # Shared frontend + editor styles
└── view.js              # Frontend-only script (optional)
```

Blocks are **dynamic by default**: `render.blade.php` renders them on each request, so their markup is not stored in `post_content`. Changing the markup updates every existing block instead of triggering "This block contains unexpected or invalid content".

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
    "render": "file:./render.blade.php"
}
```

## Editor Component (edit.jsx)

For a dynamic block, the editor should show what the page shows: render the same markup (or use `ServerSideRender`).

```jsx
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Edit({ attributes, setAttributes }) {
    return (
        <section {...useBlockProps()}>
            <RichText
                tagName="h1"
                value={attributes.heading}
                onChange={(heading) => setAttributes({ heading })}
                placeholder="Enter heading..."
            />
        </section>
    );
}
```

## Rendering with Blade (render.blade.php)

The template receives `$attributes` (array), `$content` (inner blocks HTML) and `$block` (`WP_Block`). Blade components work as in any view.

```blade
<section {!! get_block_wrapper_attributes(['class' => 'py-16']) !!}>
    <h1 class="text-3xl font-bold">{{ $attributes['heading'] ?? '' }}</h1>
    <a href="{{ esc_url_raw($attributes['ctaUrl'] ?? '#') }}" class="btn">
        {{ $attributes['ctaText'] ?? '' }}
    </a>
    {!! $content !!}
</section>
```

- `{{ }}` escapes; use `{!! !!}` only for `get_block_wrapper_attributes()` and `$content`.
- For URLs use `esc_url_raw()` inside `{{ }}` — `esc_url()` would be encoded twice.
- The render file must stay inside the block directory, or the block renders nothing and a warning is logged.
- A plain `render.php` still works.

## Registration

There is nothing to write. Pollora registers the blocks of every active theme, plugin and module on WordPress `init`: whatever holds a `resources/views/blocks` directory (or the deprecated `resources/blocks`).

- Do **not** create a `BlocksServiceProvider` or call `register_block_type()`: a provider boots after `init` over HTTP and not at all for REST requests, so its blocks would exist in WP-CLI only.
- A `BlocksServiceProvider` left by an older `pollora:make:block` is harmless and can be deleted.
- Assets resolve through the module's asset container: `theme`, `plugin.{slug}` or `module.{slug}`.

## Tailwind CSS in Blocks

### Frontend + Editor Styles (style.css)

```css
@import "tailwindcss" source(".");

.wp-block-my-theme-hero-banner {
    @apply relative py-24 px-8 rounded-xl overflow-hidden;
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

`pollora:make:block` writes this on first use:

```js
import { wordpressPlugin } from '@roots/vite-plugin';
import { globSync } from 'glob';

const blockEntries = globSync([
    './resources/views/blocks/*/{index,view}.{js,jsx,ts,tsx}',
    './resources/views/blocks/*/{editor,style}.css',
])
    .reduce((acc, file) => {
        acc[file.replace(/^\.\//, '').replace(/\.\w+$/, '')] = file;
        return acc;
    }, {});
const hasBlocks = Object.keys(blockEntries).length > 0;

// input: [..., ...Object.values(blockEntries)]
// plugins: [..., ...(hasBlocks ? [wordpressPlugin()] : [])]
// refresh: Blade files only, so block JSX hot-reloads:
//   [...refreshPaths.filter((p) => p !== 'resources/views/**'), 'resources/views/**/*.blade.php']
```

## Migrating from `resources/blocks`

The old directory is still registered, with a deprecation notice in the log, until Pollora v15.

1. `git mv resources/blocks resources/views/blocks`
2. Delete `app/Providers/BlocksServiceProvider.php` if present
3. Update the `vite.config.js` globs and full reloads (running `pollora:make:block` does it)
4. Optionally convert a static block: add `"render": "file:./render.blade.php"`, move the markup of `save.jsx` into it, set `save: () => null`

## Important Notes

- Block names follow the pattern `{namespace}/{block-slug}` (e.g., `my-theme/hero-banner`)
- Use `@import "tailwindcss" source(".")` in `style.css`, `@reference "tailwindcss"` in `editor.css`
- Custom `BlockRegistrarInterface` implementations: both methods take an optional `?string $basePath = null` (since v13.32)

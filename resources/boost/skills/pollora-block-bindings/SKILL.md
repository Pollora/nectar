---
name: pollora-block-bindings
description: Fill core block attributes with server-side values using Pollora's #[BlockBinding] sources, the pollora/post-meta, term-meta, author-meta and option sources for typed meta, and bindable Blade blocks.
---

# Pollora Block Bindings

## When to use this skill
Use this skill when a page built from core blocks (paragraph, heading, button, image) must show a value computed on the server — a meta, a count, a link — or when a Blade block's attribute should be bindable. Requires Pollora v13.34.6+ and WordPress 6.9+ (Pollora installs WordPress 7); **experimental**.

Prefer a binding on a core block to writing a custom block that only displays a value.

## Declaring a source

```php
use Pollora\Attributes\BlockBinding;
use Pollora\Attributes\BlockBinding\BindingField;
use Pollora\BlockBinding\Domain\Models\BindingContext;

#[BlockBinding('acme/event', label: 'Event', postTypes: 'event')]
final class EventBinding
{
    #[BindingField(label: 'Remaining seats')]
    public function remainingSeats(BindingContext $context, BookingRepository $bookings): string
    {
        $event = $context->meta(Event::class);          // typed meta of the block's post

        return (string) max(0, $event->capacity - $bookings->countFor($context->postId));
    }

    #[BindingField(label: 'Booking link', type: 'url')]
    public function bookingUrl(BindingContext $context): string
    {
        return route('events.book', ['event' => $context->postId]);
    }
}
```

`php artisan pollora:make:binding EventBinding` generates one (`--theme`, `--plugin`, `--module`). Discovered everywhere; the container injects dependencies into the constructor and each field.

| Parameter | Default | Effect |
|---|---|---|
| `#[BlockBinding(name)]` | required | `namespace/name`, lowercase |
| `label` | class name | Shown in the editor |
| `usesContext` | `['postId', 'postType']` | Block context read |
| `postTypes` | all | Elsewhere the block keeps its content |
| `#[BindingField(name)]` | method in snake_case | The `field` argument |
| `type` | `text` | `url` or `image` values are sanitized as URLs |

A class without fields defines `__invoke(BindingContext $context)`.

`BindingContext`: `postId`, `postType` (right inside a query loop), `termId`, `taxonomy`, `arg('name', $default)`, `attribute`, `block`, `post()` (a `Pollora\Models\Post`), `meta(Class::class)`.

A field declares its return type: `string`, `int`, `float`, `bool`, `Stringable` or `null` (an array or no return type is refused at discovery). `null` keeps the block's saved text. Pollora escapes for the place the value lands in (text escaped, URLs sanitized, `HtmlString` through `wp_kses_post()`): do not escape in the field. A field that throws is logged and reads as `null` (thrown in debug mode); in debug mode a field slower than 50 ms is logged.

## Binding in markup

```html
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"acme/event","args":{"field":"remaining_seats"}}}}} -->
<p>Seats available</p>
<!-- /wp:paragraph -->
```

In the editor, **Attributes** in the block settings offers the fields; a bound block previews its value (read-only) through `POST /wp-json/pollora/v1/block-bindings/resolve`. No JavaScript to write.

## Typed meta in core blocks

| Source | Reads | Arguments |
|---|---|---|
| `pollora/post-meta` | a typed meta of the block's post | `key`, `format`, `decimals`, `true`, `false`, `size`, `fallback` |
| `pollora/term-meta` | a typed meta of the block's term, or the queried term | same |
| `pollora/author-meta` | a typed meta of the post's author, only `#[Meta(public: true)]` | same |
| `pollora/option` | a site option listed in `config/block-bindings.php` → `options` (none by default) | `name`, `fallback` |

Values are formatted by type: dates in the site's format and language (`format`), numbers with its separators (`decimals`), booleans as Yes/No, enums by `label()`, arrays as a list; `format: raw` gives the stored value. A `#[Meta(media: true)]` attachment ID bound to an image gives its URL (`size`), `alt`, `title` or `caption` according to the attribute. Post title, link and date come from WordPress's `core/post-data`.

Post and term meta are only shown with `showInRest: true` and a key not starting with `_`. To let editors **edit** a meta from a block, bind `core/post-meta` instead: Pollora's sources are read-only.

## Bindable Blade blocks

```json
{
    "name": "acme/event-card",
    "attributes": { "title": { "type": "string" }, "ctaUrl": { "type": "string" } },
    "pollora": { "bindings": ["title", "ctaUrl"] },
    "render": "file:./render.blade.php"
}
```

The view reads `$attributes['title']` as usual and escapes it (`{{ }}`). Only a block with a `render` can be bound.

## Checking

- `php artisan pollora:doctor` — every binding in the theme's, plugins' and modules' templates, parts and patterns that can never show a value (unknown source or field, undeclared or non-public meta, unlisted option, attribute not bindable), with file, block and reason. Bindings saved in posts are not read
- `php artisan pollora:binding:list` (`--json`) — sources, fields, the meta and options each may show, bindable blocks

## Important Notes

- Never read the logged-in visitor in a source: the value would leak through the page cache
- A field runs once per post, attribute and arguments per request, but in a query loop of 20 posts with 4 bound blocks that is 80 calls: no uncached query in a field
- Run `php artisan discovery:clear` after adding a source in development

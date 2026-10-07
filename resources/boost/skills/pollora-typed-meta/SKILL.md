---
name: pollora-typed-meta
description: Declare WordPress post, term, user and comment meta as typed PHP properties with Pollora's #[Meta] attribute, then read and write them with their PHP type through Meta::of() or the Pollora models.
---

# Pollora Typed Meta

## When to use this skill
Use this skill when a post type, taxonomy, user or comment needs custom fields: declaring them, reading or writing them, exposing them in REST, validating them, or checking stored values. Requires Pollora v13.34.4+; **experimental** (the API may change).

Do not call `register_meta()`, cast `get_post_meta()` by hand or repeat a key string: declare the meta once.

## Declaring meta

A meta is a public typed property marked `#[Meta]` on the class that already carries `#[PostType]` or `#[Taxonomy]`. The type gives the meta type, the initial value the default, the name the key in snake_case:

```php
use App\Enums\EventStatus;
use Carbon\CarbonImmutable;
use Pollora\Attributes\Meta;
use Pollora\Attributes\PostType;

#[PostType('event')]
class Event
{
    #[Meta(showInRest: true, label: 'Start')]
    public ?CarbonImmutable $startsAt = null;      // key: starts_at

    #[Meta(showInRest: true, rules: ['min:0', 'max:5000'])]
    public int $capacity = 0;

    #[Meta(showInRest: true)]
    public EventStatus $status = EventStatus::Draft;   // a backed enum

    #[Meta(key: '_event_internal_ref', capability: 'manage_options')]
    public ?string $internalRef = null;
}
```

Every property needs a default or a nullable type: that is what an absent meta reads as. Run `php artisan discovery:clear` after adding a class in development.

### Objects the project does not declare

```php
use Pollora\Attributes\{CommentMeta, Meta, PostMeta, TermMeta, UserMeta};

#[PostMeta('product')]          // a plugin's post type; ['post', 'page'] for several
class ProductExtras { #[Meta(showInRest: true)] public ?string $warrantyNotice = null; }

#[TermMeta('category')]
class CategoryExtras { #[Meta(showInRest: true)] public ?string $color = null; }

#[UserMeta]
class MemberProfile { #[Meta(showInRest: true)] public bool $newsletterOptIn = false; }

#[CommentMeta]                  // every comment type: WordPress has no per-type comment meta
class ReviewMeta { #[Meta] public int $rating = 5; }
```

Several classes may target the same objects if their keys differ.

### `#[Meta]` parameters

| Parameter | Default | Effect |
|---|---|---|
| `key` | property in snake_case | Database key |
| `showInRest` | `false` | Exposes the meta in REST with a schema from its type |
| `label`, `description` | none | Editor label; `register_meta()` description |
| `sanitize` | from the type | A callable replacing it (`'wp_kses_post'` for HTML) |
| `capability` | edit right on the object | Required to write through REST; required to expose a `_` key |
| `revisions` | `false` | Versioned with post revisions (posts only) |
| `rules` | none | Laravel validation rules, the type rule implied |
| `items` | `@var list<…>` docblock | Item type of an `array`: `'string'`, `'int'`, a class… |
| `single` | `true` | On an `array`, `false` stores one row per item |
| `media` | `false` | On an `int`: an attachment ID (Block Bindings give its URL, alt, caption) |
| `public` | `false` | May be shown to anyone; required for `pollora/author-meta` |
| `control`, `group`, `hints` | from the type | Input field description for a UI driver (`config/meta.php` → `ui`) |

### Types

`string`, `int`, `float`, `bool`, dates (`DateTimeInterface`, Carbon — stored as ISO 8601 UTC), backed enums, `array` (with an item type), and a class with public typed scalar/date/enum properties (stored as an array, read back as an instance). Refused at discovery, with the class and property logged: union or missing types, an array without item type or of arrays, a non-backed enum, a non-nullable property without default, a `_` key in REST without `capability`, a key declared twice for the same objects.

## Reading and writing

```php
use Pollora\Support\Facades\Meta;

$event = Meta::of(Event::class, $postId);   // post, term, user or comment ID
$event->capacity;                           // int
$event->startsAt?->isFuture();              // CarbonImmutable|null
$event->starts_at;                          // the same meta, by its key

$event->capacity = 250;                     // checked at once
$event->fill(['status' => EventStatus::Published])->save();   // update_metadata()
$event->set('startsAt', null)->save();      // null deletes a nullable meta
```

A wrong type throws `InvalidMetaValueException`, a broken rule `MetaValidationException`, before anything is written. `get_post_meta()` keeps returning the stored string.

### On the models

```php
use Pollora\Models\Post;

class Event extends Post
{
    protected $postType = 'event';   // Post::find() then returns an Event
}

$event->capacity;                                   // typed attribute
Event::whereMeta('capacity', '>=', 100)->get();     // numbers compared as numbers
Event::whereMeta('subtitle', null)->get();          // meta absent
auth()->user()->newsletterOptIn;                    // a #[UserMeta]
```

Typed meta are not in `toArray()`. A collection of such models loads its meta in one query through WordPress's cache.

## REST and validation

With `showInRest: true` the meta appears under `meta` in the object's REST response, typed; enums list their values, dates use `date-time`. Pollora adds `custom-fields` support to a `#[PostType]` that exposes meta; for a plugin's post type targeted by `#[PostMeta]`, check it supports `custom-fields`. A REST write breaking `rules` answers 400 with the rule's message under `data.params["meta.<key>"]`. `update_post_meta()` only sanitizes.

## Checking

- `php artisan pollora:meta:list` (`--json`) — every typed meta by class, with key, type and options
- `php artisan pollora:doctor` — refused declarations, `#[PostMeta]`/`#[TermMeta]` naming a missing post type or taxonomy, `showInRest` on an object not in REST, unreadable stored values (sampled)
- `php artisan pollora:meta:audit` (`--limit`, `--json`) — reads every stored value, names those that cannot be read as their type with the object IDs, exits 1 (for CI against a copy of production), and lists keys no `#[Meta]` declares

A stored value that does not match its type throws in debug mode and reads as the default (logged) in production.

## Important Notes

- A meta exposed in REST is public data: never put a secret in a `showInRest` meta
- Renaming a property changes its key: old values stay under the old key (`pollora:meta:audit` lists it)
- Changing a property's type can make stored values unreadable: run `pollora:meta:audit` before deploying
- Bind typed meta to core blocks with `pollora/post-meta` (see the `pollora-block-bindings` skill)

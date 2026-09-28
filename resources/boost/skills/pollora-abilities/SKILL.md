---
name: pollora-abilities
description: Declare WordPress Abilities API abilities (WordPress 6.9+) with the Pollora Ability facade or the #[Ability] attribute, so AI agents and automation tools can discover and invoke site features.
---

# Pollora Abilities

## When to use this skill
Use this skill when exposing site functionality to AI agents or automation tools through the WordPress Abilities API (WordPress 6.9+). Pollora wraps it in `pollora/abilities`, installed with the framework. On an older WordPress, declarations are accepted and never published.

An ability is not an MCP tool: the MCP Adapter plugin publishes abilities over MCP, and core exposes them at `/wp-json/wp-abilities/v1/abilities`. Declare the ability once; consumers pick it up.

## Categories

Every ability belongs to a category that must exist. Declare it once, typically in a service provider. Slugs are global to the install and core claims several: prefix yours.

```php
use Pollora\Support\Facades\Ability;

Ability::category('acme-content', 'Editorial', 'Posts and pages.');
```

## Declarative API (preferred for anything non-trivial)

```php
use Pollora\Abilities\Domain\Contracts\AbilityHandler;
use Pollora\Abilities\Domain\Model\Behaviour;
use Pollora\Abilities\Domain\Model\Input;
use Pollora\Abilities\Domain\Schema\SchemaBuilder;
use Pollora\Attributes\Ability;

#[Ability(
    name: 'acme/create-post',
    description: 'Creates a post from a title and a status.',
    category: 'acme-content',
    behaviour: Behaviour::Creates,
)]
final class CreatePost implements AbilityHandler
{
    public function schema(SchemaBuilder $schema): void
    {
        $schema->string('title', 'Title of the post to create.', required: true);
        $schema->enum('status', 'Publication status.', ['draft', 'publish'], default: 'draft');
    }

    public function authorize(Input $input): mixed
    {
        return current_user_can('edit_posts')
            ?: new WP_Error('forbidden', 'You cannot create posts.', ['status' => 403]);
    }

    public function handle(Input $input): mixed
    {
        return ['id' => wp_insert_post([
            'post_title' => $input->string('title'),
            'post_status' => $input->string('status', 'draft'),
        ])];
    }
}
```

The class is discovered anywhere discovery scans and built through the container. A class with `#[Ability]` that does not implement `AbilityHandler` is logged as an error. A missing category is declared for you.

## Imperative API

```php
Ability::define('acme/get-posts')
    ->description('Returns the most recent posts, newest first.')
    ->category('acme-content')
    ->input(fn (SchemaBuilder $schema) => $schema
        ->integer('limit', 'How many posts to return.', default: 10, minimum: 1, maximum: 100))
    ->can(fn (Input $input): bool => current_user_can('edit_posts'))
    ->using(fn (Input $input): array => /* ... */ []);
```

Nothing is registered until `using()` or `handledBy()` supplies a body. Pollora queues declarations and publishes them on WordPress's abilities init hooks — never hook those yourself.

## Rules

- **Names** are `namespace/slug`, lowercase alphanumerics and single dashes; a bare slug, an empty label or description is refused at declaration.
- **Permissions default to refusing**: without `can()` / `authorize()` the ability refuses everything. The check receives the same input as the body — check `edit_post` on the given id rather than a blanket `edit_posts`. Return a `WP_Error` to explain a refusal.
- **Behaviour** (advisory hints for clients): `Reads` (default), `Creates`, `Updates`, `Deletes` — facade `->reads()`, `->creates()`, `->updates()`, `->deletes()`. Declare it truthfully; the permission check is what protects the site.
- **Input** is a defensive reader: `$input->string()`, `integer(default:, max:)`, `float()`, `id()`, `boolean()`, `stringList()` never throw on a missing or malformed value.

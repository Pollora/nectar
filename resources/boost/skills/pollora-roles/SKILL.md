---
name: pollora-roles
description: Check WordPress capabilities through Laravel authorization, and declare roles in code with Pollora's #[Role], #[ModifyRole] and #[CapabilitySet] attributes instead of storing them in the database.
---

# Pollora Roles & Capabilities

## When to use this skill
Use this skill when restricting who can do something (routes, Blade, REST, PHP), giving a post type its own capabilities, creating or changing a role, or cleaning up roles. Checking capabilities is stable; declaring roles and checking role classes is **experimental** (v13.34.4+; `hasRole()`, `role:`, `@role` with classes since v13.34.5).

Do not call `add_role()` / `add_cap()`: they write to the database once and drift between environments. Declare roles in code.

## Checking capabilities

Laravel's authorization answers with WordPress capabilities:

```php
$user = auth()->user();                 // Pollora\Models\User
$user->can('edit_posts');
$user->can('edit_post', $post);         // meta capability, map_meta_cap()
Gate::authorize('manage_options');      // 403 otherwise

Route::get('/reports', ReportController::class)->middleware('can:manage_options');
```

```blade
@can('edit_post', $post) … @endcan
@canany(['edit_posts', 'moderate_comments']) … @endcanany
@role('editor', EventManager::class) … @endrole
```

In a `#[WpRestRoute]`, use `permissionCallback: new Can('edit_posts')` (or `new Can('edit_post', parameter: 'id')`).

**Check a capability, not a role**: `can:export_attendees` stays right when a second role gets the capability; `role:event_manager` does not.

## A post type with its own capabilities

```php
use Pollora\Attributes\PostType;
use Pollora\Attributes\PostType\CapabilityType;
use Pollora\Attributes\PostType\MapMetaCap;

#[PostType('event')]
#[CapabilityType('event')]
#[MapMetaCap]
class Event {}
```

WordPress then expects `edit_events`, `publish_events`… that no role has: Pollora gives them to the super roles (`administrator` by default, `config/roles.php` → `super_roles`); grant them to other roles with `#[GrantsPostType]`.

## Declaring a role

`php artisan pollora:make:role EventManager` generates one in `app/Cms/Roles`.

```php
use Pollora\Attributes\Role;
use Pollora\Attributes\Role\{Grants, GrantsPostType, Without};
use Pollora\Role\Domain\Enums\Access;

#[Role('event_manager', label: 'Event manager', inherits: 'author', textDomain: 'my-theme')]
#[GrantsPostType(Event::class, Access::Editor)]     // Contributor < Author < Editor
#[Grants(EventCap::ExportAttendees, 'moderate_comments')]
#[Without('publish_posts')]
final class EventManager {}
```

| Attribute | Effect |
|---|---|
| `#[Role(slug, label, inherits, allowSensitive, textDomain)]` | Declares the role; `inherits` is a slug or another `#[Role]` class, followed live |
| `#[Grants(...)]` / `#[Without(...)]` | Adds / removes capabilities (strings or enum cases); repeatable |
| `#[GrantsPostType(class or slug, Access)]` | Capabilities of a post type with `#[CapabilityType]` |
| `#[GrantsTaxonomy(class or slug)]` | Term capabilities of a taxonomy with its own `#[Capabilities]` |
| `#[ModifyRole('editor')]` | Adjusts a role the project does not own (core, WooCommerce, a plugin) with the same `Grants`/`Without` attributes |

Project capabilities go in a backed enum:

```php
#[CapabilitySet(label: 'Events')]
enum EventCap: string
{
    case ExportAttendees = 'export_attendees';
}
// Gate::allows(EventCap::ExportAttendees); super roles receive every case
```

### How it works

Declared roles are injected into WordPress's role registry **in memory on every request** (`wp_roles_init`), never written to `{prefix}user_roles`. The code is the source of truth: deploy and the role follows; remove the class and the role is gone (Pollora undoes what a plugin's `add_cap()` wrote back). Role editor plugins cannot change a declared role.

### Safety rules (enforced at discovery, the declaration is refused and logged)

- Sensitive capabilities (`manage_options`, `edit_users`, `promote_users`, `unfiltered_html`, `install_plugins`, `edit_themes`, `update_core`…) need `allowSensitive: true`
- Core roles cannot be redeclared with `#[Role]`: use `#[ModifyRole]`
- No inheritance from a super role, no loop, no slug declared twice, no capability both granted and removed
- `#[Without]` removes a capability; it never stores `false`

## Checking roles in code

```php
$user->roles();                         // ['author', 'event_manager']
$user->hasRole(EventManager::class);    // or slugs: hasRole('editor', 'administrator')
$user->assignRole(EventManager::class); // through WP_User; refuses an unknown role
$user->removeRole('event_manager');

Route::middleware('role:event_manager,editor');
Route::get('/scan', ScanController::class)->middleware(EnsureUserHasRole::using(EventManager::class));
```

`assignRole()` / `removeRole()` do not check the caller's rights: check `promote_users` where a user can trigger them. Your own user model gets them with the `Pollora\Models\Concerns\HasRoles` trait (needs `toWpUser()`).

## Inspecting and migrating

- `pollora:roles:list` / `pollora:roles:show event_manager` (`--json`) — roles with their origin and users; effective capabilities and where each comes from
- `pollora:doctor` — users still carrying a removed role, a `default_role` that no longer exists, capabilities given to users one by one, declarations that could not apply
- `pollora:roles:prune --reassign=subscriber` — takes removed roles off users; `pollora:roles:import venue_staff --inherits=author` — turns a stored role into a `#[Role]` class (review it); `pollora:roles:dump` — writes the code's roles to the option for tools that read the database. All three are a dry run unless `--force`

## Important Notes

- A tool reading `user_roles` directly, without loading the site, does not see declared roles (use `pollora:roles:dump`)
- Roles from plugins and themes are injected after WordPress built its roles, and the current user's capabilities are recomputed
- Run `php artisan discovery:clear` after adding a role class in development

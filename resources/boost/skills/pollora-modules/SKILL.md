---
name: pollora-modules
description: Create and run Laravel Modules (nwidart) in Pollora projects — lean scaffold, shared Vite build, discovery, activation connectors, the Plugins › Modules view and update checks.
---

# Pollora Module Development

## When to use this skill
Use this skill when creating or working with Laravel Modules (nwidart/laravel-modules) in a Pollora project: a module groups a project's own features (post types, hooks, blocks, routes…) and can be switched on and off.

## Creating a Module

```bash
php artisan pollora:make:module Portfolio
```

It downloads the Pollora/module-default template, enables the module, runs `composer dump-autoload` and npm. The default module has **no service provider**: classes in `app/` declared with attributes are discovered.

```
Modules/Portfolio/
├── app/Cms/Hooks/PortfolioHooks.php   # #[Action] example, discovered
├── resources/assets/app.js, app.css   # Tailwind CSS v4 (no preflight)
├── resources/views/blocks/            # Gutenberg blocks, registered by Pollora
├── composer.json                      # PSR-4 Modules\Portfolio\ → app/
├── module.json                        # "providers": []
├── package.json
└── vite.config.js                     # @pollora/vite-config, type "module"
```

Laravel layers are opt-in: `--provider`, `--routes` (implies `--provider`), `--api` (implies `--routes`), `--config`, `--database`, `--tests` (Pest), `--full`; `--no-assets` for a PHP-only module; `--no-enable`, `--no-npm`, `--offline`. nwidart's `module:make` writes the same lean module unless the project published nwidart's own `config/modules.php`.

Generate into a module with `--module` — the class lands where nwidart found the module, under the namespace its `composer.json` maps onto `app/`:

```bash
php artisan pollora:make:post-type Project --module=Portfolio
php artisan pollora:make:block project-card --module=Portfolio   # block "portfolio/project-card"
```

## Discovery

```php
// Modules/Portfolio/app/Cms/PostTypes/Project.php
namespace Modules\Portfolio\Cms\PostTypes;

use Pollora\Attributes\PostType;
use Pollora\Attributes\PostType\HasArchive;
use Pollora\Attributes\PostType\PubliclyQueryable;
use Pollora\Attributes\PostType\ShowInRest;

#[PostType('project')]
#[PubliclyQueryable]
#[HasArchive]
#[ShowInRest]
class Project {}
```

Post types, taxonomies, `#[Action]`/`#[Filter]`, `#[WpRestRoute]`, `#[Schedule]` and blocks in `resources/views/blocks` are discovered in every enabled module. Run `php artisan discovery:clear` after adding such a class when the discovery cache is on.

## Frontend

```js
// Modules/Portfolio/vite.config.js
import { defineConfig } from 'vite';
import pollora from '@pollora/vite-config';

export default defineConfig({
    plugins: [pollora({ type: 'module', name: 'portfolio' })],
});
```

Builds into `public/build/module/portfolio` (hot file `public/portfolio.hot`, dev server port 5175 or `VITE_PORT`). Every enabled module with a `vite.config.js` gets the `module.<kebab-name>` asset container:

```php
Asset::add('portfolio/app', 'app.js')->container('module.portfolio')->toFrontend()->useVite();
```

A module made by nwidart's stock `module:make` builds into `public/build-<lower>`, where Pollora never looks: `php artisan pollora:module:frontend Portfolio` replaces its `package.json` and `vite.config.js` (keeping `.bak` copies).

## Enabling and Disabling

```bash
php artisan module:enable Portfolio
php artisan module:disable Portfolio
```

Also from **Plugins › Modules** in wp-admin (capability `activate_plugins`). A change applies from the next request. `MODULES_ADMIN_TOGGLE=false` turns the admin switches off.

Where the state lives is a **connector**, chosen in a published `config/modules.php` (`php artisan vendor:publish --tag=pollora-modules`) — nwidart reads its activator before any provider, so never configure it from a provider:

- `json` (default): `modules_statuses.json` — reset by a deployment unless committed
- `database`: the `pollora_modules` WordPress option (survives deployments)
- `config`: `MODULES_ENABLED` / `MODULES_DISABLED`, read-only
- your own: a `ModuleStateConnector` class in `connectors.<name>.class`, or `ModuleConnectors::extend()` in `bootstrap/app.php`

`MODULES_LOCKED_ENABLED` / `MODULES_LOCKED_DISABLED` force states. Switch connector with `php artisan pollora:module:connector database --import`, then `MODULES_CONNECTOR=database`. Switches fire `ModuleEnabled` / `ModuleDisabled`.

## Composer modules and updates

A module installed with Composer lands in `Modules/` through the skeleton's `installer-paths` rule and carries its package version; local modules have none. `php artisan pollora:module:outdated` checks for newer releases (also shown in Plugins › Modules, Site Health and `pollora:status`).

## Important Notes

- Prefer attributes in `app/` over a service provider; add one with `--provider` only when the module registers services, config or routes
- Module views are namespaced by the lower-case module name: `view('portfolio::project.show')` (with `--provider`)
- `pollora:doctor` checks module builds and activation (fallback connector, states for missing modules, stale caches, ignored `MODULES_*` settings)
- Use `module_path('Portfolio', 'relative/path')` to reference module files

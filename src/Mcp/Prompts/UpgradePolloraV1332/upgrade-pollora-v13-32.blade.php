@verbatim
# Pollora 13.x to 13.32 Upgrade Specialist

You are upgrading a Pollora project from 13.x (13.0 to 13.4.x) to 13.32. The framework version now tracks the Laravel release it targets, hence the jump from 13.4 to 13.32. Work through the steps in order, measure each change (run the site, the tests, `curl` the pages), and do not report success on a step you have not checked.

## Upgrade Process

### 1. Assess Current State

- Read `composer.json`: the `pollora/framework`, `laravel/framework` and `johnpbloch/wordpress` constraints, the `repositories` block, the `scripts`, and `extra.enable-patching`
- Run the test suite and note the baseline
- Search for the patterns listed in each section below and list every file affected before changing anything

### 2. Create Safety Net

- Work on a dedicated branch
- Commit `composer.lock` and `patches.lock.json` (if any) before updating

### 3. Apply the Sections Below, in Order

Dependencies first, then code, then the skeleton files, then themes.

### 4. Verify

- `composer install` exits 0 **and** WordPress is patched: `grep -c "function __wp" public/cms/wp-includes/l10n.php` answers 1
- `php artisan discovery:clear && php artisan pollora:status`
- Load the home page, a post, a page, a 404 and wp-admin; with `WP_DEBUG` on, `curl -s <url> | grep pollora:template` says which template answered
- Open the block editor: the theme's blocks are in the inserter

---

# Upgrading from Pollora 13.x to 13.32

## Dependencies

**Likelihood Of Impact: High**

```json
"require": {
    "johnpbloch/wordpress": "^7.1",
    "laravel/framework": "^13.35",
    "pollora/framework": "^13.35"
}
```

- The framework's version tracks Laravel's: v13.35 requires `illuminate/*` `^13.35` (v13.34 required `^13.34`, the 13.32 betas `^13.32`). Upgrade Laravel first if needed
- Since v13.35.1, `template_redirect` fires once every service provider has booted, still before routing: code that checked `did_action('template_redirect')` from a provider's `boot()` must move to a later hook
- Since v13.34.0 the releases are stable: the constraint needs no `@beta` flag
- WordPress plugins and themes come from wp-packages instead of wpackagist. Replace the repository and rename the packages (`wpackagist-plugin/x` → `wp-plugin/x`, `wpackagist-theme/x` → `wp-theme/x`):

```json
"repositories": [
    { "type": "composer", "url": "https://repo.wp-packages.org" }
]
```

## Patching WordPress (`cweagans/composer-patches` 2)

**Likelihood Of Impact: High**

The framework patches WordPress core so that its `__()` becomes `__wp()` and Laravel's helper keeps the name. composer-patches moves from 1 to 2, which behaves differently:

- It ignores `extra.enable-patching`: remove it
- Once `patches.lock.json` exists it reads **only** the lock, and it patches a package only when that package is installed. A lock missing the WordPress patch leaves WordPress unpatched while `composer install` exits 0 — two functions named `__()`, a fatal error
- Add to `scripts.post-update-cmd`, before anything else:

```json
"post-update-cmd": [
    "@composer patches-relock --no-interaction",
    "@composer patches-repatch --no-interaction",
    "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
]
```

- Commit the generated `patches.lock.json`
- Check: `grep -c "function __wp" public/cms/wp-includes/l10n.php` must answer 1

## Artisan Commands Renamed

**Likelihood Of Impact: Medium**

Commands follow the Laravel colon convention: `pollora:env-setup` → `pollora:env:setup`, `pollora:make-theme` → `pollora:make:theme`, and likewise for `make-plugin`, `make-block`, `make-model`, `make-action`, `make-filter`, `make-posttype` (→ `make:post-type`), `make-taxonomy`, `make-wp-cli`, `delete-theme` (→ `theme:delete`). The old names still work as aliases.

Search `composer.json` scripts, CI files, deploy scripts and docs for `pollora:[a-z]+-`. At least update `post-autoload-dump`:

```json
"@php artisan pollora:env:setup --install"
```

## Modules Extracted to Packages

**Likelihood Of Impact: Low (only code importing internal classes)**

The Hook, Option and Ajax modules moved to `pollora/hook`, `pollora/option` and `pollora/ajax`, installed with the framework. The facades (`Action`, `Filter`, `Option`, `Ajax`) and the attributes do not change. Code importing the internal classes must follow them:

| Before | After |
|---|---|
| `Pollora\Hook\Domain\Contracts\{Action,Filter,HookInterface,CallbackResolverInterface}` | `Pollora\Hook\Domain\Contract\…` |
| `Pollora\Hook\Domain\Services\AbstractHook` | `Pollora\Hook\Domain\Service\AbstractHook` |
| `Pollora\Hook\Infrastructure\Services\{Action,Filter}` | `Pollora\Hook\Adapter\Out\WordPress\{Action,Filter}` |
| `Pollora\Option\Application\Services\OptionService` | `Pollora\Option\Application\Service\OptionService` |
| `Pollora\Option\Domain\Contracts\OptionRepositoryInterface` | `Pollora\Option\Domain\Contract\OptionRepositoryInterface` |
| `Pollora\Option\Domain\Exceptions\*`, `Domain\Models\Option`, `Domain\Services\OptionValidationService` | `Domain\Exception\*`, `Domain\Model\Option`, `Domain\Service\OptionValidationService` |
| `Pollora\Option\Infrastructure\Repositories\WordPressOptionRepository` | `Pollora\Option\Adapter\Out\WordPress\WordPressOptionRepository` |
| `Pollora\Ajax\Domain\Models\AjaxAction` | `Pollora\Ajax\Domain\Model\AjaxAction` |
| `Pollora\Ajax\Domain\Contracts\AjaxActionRegistrarInterface` | `Pollora\Ajax\Port\Out\AjaxActionRegistrarPort` |
| `Pollora\Ajax\Infrastructure\Services\AjaxFactory` | `Pollora\Ajax\Factory\AjaxFactory` |

Search: `Pollora\\(Hook|Option|Ajax)\\(Domain|Application|Infrastructure)`.

## `Loop` and `Query` Facades

**Likelihood Of Impact: Medium**

Their classes were deleted long ago but the aliases stayed, so a page calling `Loop::` or `Query::` threw `Class not found`. The aliases are gone. Search `Loop::`, `Query::`. In Blade, use Sage Directives (`@posts`, `@title`, `@content`); in PHP, the WordPress function the method wrapped:

| Before | After |
|---|---|
| `Loop::title($post)` | `get_the_title($post)` |
| `Loop::content()` | `apply_filters('the_content', get_the_content())` |
| `Loop::excerpt($post)` | `apply_filters('the_excerpt', get_the_excerpt($post))` |
| `Loop::thumbnail($size, $attr, $post)` | `get_the_post_thumbnail($post, $size, $attr)` (argument order changes) |
| `Loop::link($post)` | `get_permalink($post)` |
| `Loop::terms($taxonomy, $post)` | `get_the_terms($post, $taxonomy) ?: []` (argument order changes) |
| `Loop::postClass($class, $id)` | `'class="'.implode(' ', get_post_class($class, $id)).'"'` |
| `Loop::paginate($args)` | `paginate_links($args)` |

`Query::` is replaced by the `PostQuery`, `MetaQuery` and `TaxQuery` facades, or `new WP_Query(...)`.

## `Translater` Requires Its Domain

**Likelihood Of Impact: Low**

`new Translater($items)` now needs the text domain: `new Translater($items, 'my-theme')`. The old `'wordpress'` default relied on a key prefix that `pollora/helper-overrider` 1.2 removed, so it silently returned values untranslated. Search `Translater`.

How `__()` routes, for reference: a string second argument is a WordPress text domain (`__('Text', 'my-theme')`), an array is a Laravel call with replacements, no argument tries Laravel then WordPress's `default` domain.

## Blocks

**Likelihood Of Impact: Medium (themes, plugins and modules with blocks)**

- Blocks move from `resources/blocks/{slug}` to `resources/views/blocks/{slug}`. The old directory is still registered, with a deprecation notice in the log, until v15: `git mv resources/blocks resources/views/blocks`
- Pollora registers the blocks of every theme, plugin and module itself on `init`. Delete `app/Providers/BlocksServiceProvider.php`: a provider boots too late over HTTP and not at all for REST, so its blocks existed in WP-CLI only
- In `vite.config.js`, glob `./resources/views/blocks/*/{index,view}.{js,jsx,ts,tsx}` and `./resources/views/blocks/*/{editor,style}.css`, and limit full reloads to Blade files: `refresh: [...refreshPaths.filter((p) => p !== 'resources/views/**'), 'resources/views/**/*.blade.php']`. Running `pollora:make:block` once does it
- `pollora:make:block` now makes dynamic blocks rendered by `render.blade.php` by default; `--static` gives the former `save.jsx` block; `--dynamic` is deprecated
- Custom `BlockRegistrarInterface` implementations: `registerDirectory()` and `registerBlock()` gain a final `?string $basePath = null` parameter

## Skeleton Files

**Likelihood Of Impact: Medium**

Compare with the skeleton (`Pollora/pollora`) and bring over:

- **`routes/web.php`**: remove the `Route::wp('home'|'single'|'page'|'404', …)` entries the old skeleton declared. They took priority over the template hierarchy, so a theme's `single.blade.php` was ignored in favour of `view('post')`. Keep `Route::wp()` only for requests that need controller logic
- **Cache table**: `.env.example` uses `CACHE_STORE=database`; add Laravel's `cache` and `cache_locks` migration if missing (`php artisan make:cache-table`), or a fresh install answers 500
- **`public/.htaccess`**: the trailing slash redirect now keeps the scheme behind a TLS proxy, redirects only GET and HEAD, and leaves `/wp-json` to WordPress:

```apache
# Redirect Trailing Slashes If Not A Folder...
RewriteCond %{HTTPS} =on [OR]
RewriteCond %{HTTP:X-Forwarded-Proto} =https
RewriteRule ^ - [E=POLLORA_SCHEME:https]
RewriteCond %{ENV:POLLORA_SCHEME} !=https
RewriteRule ^ - [E=POLLORA_SCHEME:http]
RewriteCond %{REQUEST_METHOD} ^(GET|HEAD)$
RewriteCond %{REQUEST_URI} !^/wp-json(/|$)
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} (.+)/$
RewriteRule ^ %{ENV:POLLORA_SCHEME}://%{HTTP_HOST}%1 [L,R=301]
```

- **`composer dev`** runs `@php artisan dev`

## Themes: `theme.json` from `@theme`

**Likelihood Of Impact: Medium (themes built with `wordpressThemeJson`)**

Check the theme for three defects that combine into an editor offering some 290 Tailwind colours and semantic colours that resolve to nothing:

1. `@import "tailwindcss" theme(static);` in `app.css` → `@import "tailwindcss";`
2. A `copy-theme-json` step in `vite.config.js` copying the built `theme.json` over the theme's own → remove it, and remove the generated `palette`, `fontSizes`, `fontFamilies` and `radiusSizes` from the theme's `theme.json` (the base wins over `@theme` on any slug it defines)
3. `@theme` values written `var(--wp--preset--color--x, #hex)` → concrete values in `@theme static`, plus a rule pointing the utilities at the presets:

```css
@theme static {
    --color-primary: #1f2937;
    --text-base: 1rem;
    --radius-lg: 0.5rem;
}

@layer base {
    :root {
        --color-primary: var(--wp--preset--color--primary, #1f2937);
    }
}
```

Check: the built `public/build/theme/{slug}/assets/theme.json` lists only the theme's colours, each with a value. See the `pollora-theming` skill for keeping a value out of Tailwind.

## New in 13.32 (optional)

- `pollora_register(ModuleType::Theme)` in `functions.php`; `pollora_register(ModuleType::Plugin, 'slug', __DIR__)` in a plugin
- `#[Ajax('action', access: AjaxAccess::ALL)]` for admin-ajax handlers (logged-in only by default)
- `#[Ability(...)]` and the `Ability` facade for the WordPress Abilities API (WordPress 6.9+) — see the `pollora-abilities` skill
- `#[SkipDiscovery]` to keep a class out of discovery; `config/discovery.php` (publishable) to skip classes (`skip_classes`) or paths (`skip_paths`)
- `config/login.php` in a theme styles the login screen from `theme.json`
- `get_theme_file_uri()` returns the URL the build gave a theme file
- With `WP_DEBUG` on, each page rendered through the hierarchy carries `<!-- pollora:template="…" path="…" -->`

## Post-Upgrade Cleanup

```bash
php artisan discovery:clear
php artisan optimize:clear
php artisan migrate
cd themes/your-theme && npm install && npm run build
```
@endverbatim

---
name: pollora-hooks
description: Register and manage WordPress actions and filters using Pollora PHP 8 attributes and facades, including asynchronous actions (->async(), #[Async]) run after the request.
---

# Pollora Hooks Development

## When to use this skill
Use this skill when registering WordPress actions or filters, whether via PHP attributes (declarative) or facades (imperative), or when an action should run after the request instead of inside it.

## Attribute-Based Hooks (Recommended)

Create a hookable class and decorate methods with `#[Action]` or `#[Filter]`:

```php
<?php

namespace Theme\MyTheme\Cms\Hooks;

use Pollora\Attributes\Action;
use Pollora\Attributes\Filter;

class ContentHooks
{
    #[Action('init', priority: 20)]
    public function onInit(): void
    {
        // Runs on WordPress 'init' hook
    }

    #[Action('wp_enqueue_scripts')]
    public function enqueueAssets(): void
    {
        // Enqueue custom scripts/styles
    }

    #[Filter('the_content', priority: 10)]
    public function filterContent(string $content): string
    {
        return str_replace('old-class', 'new-class', $content);
    }

    #[Filter('the_title')]
    public function filterTitle(string $title, int $postId): string
    {
        return $title;
    }
}
```

### Dependency Injection

Hookable classes support constructor injection via Laravel's service container:

```php
class NotificationHooks
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly LoggerInterface $logger,
    ) {}

    #[Action('wp_login')]
    public function onLogin(string $username): void
    {
        $this->notifications->send("User {$username} logged in");
        $this->logger->info("Login: {$username}");
    }
}
```

### Placement

Place hookable classes where the discovery system scans:
- `app/Cms/Hooks/` in themes
- `app/Hooks/` in the main application
- `app/Hooks/` in modules

## Facade-Based Hooks (Imperative)

For programmatic hook management:

```php
use Pollora\Support\Facades\Action;
use Pollora\Support\Facades\Filter;

// Register hooks
Action::add('init', [MyHandler::class, 'boot']);
Action::add('wp_footer', fn () => echo '<!-- Custom footer -->');
Filter::add('the_content', [ContentHandler::class, 'filter']);

// Execute hooks
Action::do('my_custom_event', $arg1, $arg2);
$modified = Filter::apply('my_custom_filter', $original, $arg1);

// Check and remove
if (Action::exists('my_custom_event')) { /* ... */ }
Action::remove('init', [MyHandler::class, 'boot']);

// Retrieve callbacks
$callbacks = Action::callbacks('init');
```

## Asynchronous Actions

An action whose work is slow or calls a third party (CRM, ERP, email, indexing) can run after the request: it is queued when the hook fires, then run by a Laravel queue worker, Action Scheduler or WP-Cron. Only actions: a filter returns a value and cannot be deferred.

```php
use Pollora\Attributes\Action;
use Pollora\Attributes\Async;
use Pollora\Hook\Async\AsyncContext;

class SyncEventToCrm
{
    #[Action('save_post_event', priority: 20)]
    #[Async(tries: 3, unique: true, when: 'isRealSave', capture: 'captureStatus')]
    public function handle(int $postId, CrmClient $crm, AsyncContext $context): void
    {
        // $crm comes from the container; $context->get('status') was recorded at trigger time
    }

    public function isRealSave(int $postId): bool
    {
        return ! wp_is_post_revision($postId) && ! wp_is_post_autosave($postId);
    }

    public function captureStatus(int $postId): array
    {
        return ['status' => get_post_status($postId)];
    }
}

// Same with the facade
Action::add('woocommerce_order_status_completed', [OrderExporter::class, 'export'])
    ->async()->delay(60)->tries(3)->backoff([30, 300])->onQueue('integrations');
```

- Options (chained or named on `#[Async]`): `delay`, `via` (`queue`, `action-scheduler`, `wp-cron`, `sync`), `onQueue`, `unique` (`true` or seconds), `tries`, `backoff`, `asUser`, `capture`, `when`, `keepMissing`, `except` (hooks that stay synchronous).
- `#[Async]` on a class applies to every `#[Action]` method; a method's own `#[Async]` replaces it.
- An `#[Async]` that cannot be honoured (unknown hook in `except`, missing or non-public `capture`/`when`, invalid `tries`/`backoff`/`unique`, on a `#[Filter]`, without `#[Action]`) is logged and the action **runs synchronously**; `pollora:doctor` lists it.
- Arguments travel as values; `WP_Post`, `WP_Term`, `WP_User`, `WP_Comment` and Eloquent models travel by ID and are reloaded at execution. Any other object is refused. Capture an ID, never a secret: captured values sit in the database until execution.
- The handler runs without a current user unless `asUser`; site and locale are restored.
- Default driver `auto`: the Laravel queue only once `HOOKS_ASYNC_CONNECTION` is set, then Action Scheduler, then WP-Cron. `config/hooks.php`: `php artisan vendor:publish --tag=pollora-hooks`. `HOOKS_ASYNC_DRIVER=sync` runs everything at once in development.
- **Something must run the queue**: Pollora sets `DISABLE_WP_CRON`, so WP-Cron and Action Scheduler need a system cron requesting `wp-cron.php`; the queue needs `php artisan queue:work`.

Rules to respect in the handler:
- **At least once**: a retry or a double trigger can run it twice — make it safe to replay (check before sending, upsert).
- **No order** between asynchronous actions on the same hook, and a **variable delay**.
- **Prefer a class to a closure**: a class runs the deployed code, a closure the code as it was when queued.
- It cannot redirect, print, or change what the current request reads.

Testing:

```php
use Pollora\Hook\Async\Async;

Async::fake();
// ... fire the hook
Async::assertDispatched(SyncEventToCrm::class, fn ($payload, int $delay) => $payload->captured['status'] === 'publish');
Async::assertDispatchedTimes(SyncEventToCrm::class, 1);
Async::runDispatched(); // run what was recorded
```

`php artisan pollora:async:list` shows every asynchronous action, its driver and options, and where the default driver comes from.

## Generating Hook Classes

```bash
php artisan pollora:make:action MyAction
php artisan pollora:make:action SyncEventToCrm --hook=save_post_event --async   # with #[Async]
php artisan pollora:make:filter MyFilter
```

## Important Notes

- **Prefer attributes** over facades for hooks that should always run — they're auto-discovered and more maintainable
- Use **facades** for conditional or dynamic hook registration (e.g., in service provider `boot()` methods)
- Constructor dependencies are injected automatically in attribute-based hooks
- Run `php artisan discovery:clear` after adding new hookable classes
- Priority defaults to 10 if not specified
- Filter methods must return a value; action methods return void
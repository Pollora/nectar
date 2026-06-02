---
name: pollora-hooks
description: Register and manage WordPress actions and filters using Pollora PHP 8 attributes and facades.
---

# Pollora Hooks Development

## When to use this skill
Use this skill when registering WordPress actions or filters, whether via PHP attributes (declarative) or facades (imperative).

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

## Generating Hook Classes

```bash
php artisan pollora:make-action MyAction
php artisan pollora:make-filter MyFilter
```

## Important Notes

- **Prefer attributes** over facades for hooks that should always run — they're auto-discovered and more maintainable
- Use **facades** for conditional or dynamic hook registration (e.g., in service provider `boot()` methods)
- Constructor dependencies are injected automatically in attribute-based hooks
- Run `php artisan discovery:clear` after adding new hookable classes
- Priority defaults to 10 if not specified
- Filter methods must return a value; action methods return void
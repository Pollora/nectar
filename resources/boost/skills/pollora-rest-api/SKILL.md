---
name: pollora-rest-api
description: Build WordPress REST API endpoints using Pollora WpRestRoute attributes with automatic discovery and permission management.
---

# Pollora REST API Development

## When to use this skill
Use this skill when creating WordPress REST API endpoints using Pollora's attribute-based approach or theme API routes.

## Attribute-Based REST API (WordPress REST)

### Basic Endpoint

```php
<?php

namespace Theme\MyTheme\Cms\Api;

use Pollora\Attributes\WpRestRoute;
use Pollora\Attributes\WpRestRoute\Method;
use WP_REST_Response;

#[WpRestRoute(
    namespace: 'my-theme/v1',
    route: 'items'
)]
class ItemsAPI
{
    #[Method('GET')]
    public function index(): WP_REST_Response
    {
        return new WP_REST_Response([
            'items' => ['Item 1', 'Item 2'],
        ]);
    }

    #[Method('POST')]
    public function store(\WP_REST_Request $request): WP_REST_Response
    {
        $name = $request->get_param('name');

        return new WP_REST_Response([
            'success' => true,
            'item' => $name,
        ], 201);
    }
}
```

Endpoint: `GET /wp-json/my-theme/v1/items`

### Route Parameters

```php
#[WpRestRoute(
    namespace: 'my-theme/v1',
    route: 'documents/(?P<documentId>\d+)'
)]
class DocumentAPI
{
    #[Method('GET')]
    public function get(int $documentId): WP_REST_Response
    {
        return new WP_REST_Response([
            'documentId' => $documentId,
        ]);
    }

    #[Method(['PUT', 'PATCH'])]
    public function update(\WP_REST_Request $request, int $documentId): WP_REST_Response
    {
        return new WP_REST_Response([
            'updated' => true,
            'documentId' => $documentId,
        ]);
    }

    #[Method('DELETE')]
    public function delete(int $documentId): WP_REST_Response
    {
        return new WP_REST_Response(null, 204);
    }
}
```

Endpoint: `GET /wp-json/my-theme/v1/documents/18`

### Permissions

Use built-in permission classes or create custom ones:

```php
use Pollora\Attributes\WpRestRoute\Permissions\IsAdmin;
use Pollora\Attributes\WpRestRoute\Permissions\IsLoggedIn;

// Built-in permissions
#[WpRestRoute(
    namespace: 'my-theme/v1',
    route: 'admin/settings',
    permissionCallback: IsAdmin::class
)]
class SettingsAPI {}

// Custom permission
use Pollora\WpRest\Domain\Contracts\Permission;

class CanManageBooks implements Permission
{
    public function allow(\WP_REST_Request $request): bool|\WP_Error
    {
        return current_user_can('edit_books')
            ? true
            : new \WP_Error('forbidden', __('You cannot manage books.'), ['status' => 403]);
    }
}

#[WpRestRoute(
    namespace: 'my-theme/v1',
    route: 'books',
    permissionCallback: CanManageBooks::class
)]
class BooksAPI {}
```

## Theme API Routes (Lightweight Mode)

For performance-critical API endpoints, themes can define routes that load WordPress **without plugins** (~100ms vs ~1.3s):

```php
// themes/my-theme/routes/api.php
use Illuminate\Support\Facades\Route;

Route::get('/products/search', [ProductSearchController::class, 'search']);
Route::get('/products/{id}', [ProductSearchController::class, 'show']);
```

Endpoint: `GET /api/products/search`

### Plugin Loading Control

```php
// config/wordpress.php
'api_plugins' => [],                           // No plugins (fastest, default)
'api_plugins' => ['woocommerce', 'acf-pro'],   // Selective loading
'api_plugins' => ['*'],                        // All plugins
```

## Choosing Between Approaches

| Feature | WpRestRoute Attributes | Theme API Routes |
|---------|----------------------|------------------|
| WordPress REST API | Yes (`/wp-json/...`) | No (`/api/...`) |
| Plugin access | Full WordPress context | Configurable (none/selective/all) |
| Performance | Standard (~1.3s) | Fast (~100ms without plugins) |
| Authentication | WordPress nonces/cookies | Laravel middleware |
| Discovery | Auto-discovered | File-based (`routes/api.php`) |

## Important Notes

- REST API classes are auto-discovered — no manual `register_rest_route()` needed
- Route parameters use WordPress regex syntax: `(?P<paramName>\d+)`
- Method parameters are auto-injected from route parameters by name
- The `WP_REST_Request` object is available via type-hint
- Run `php artisan discovery:clear` after adding new REST API classes
- Use theme API routes when you need maximum performance and don't need full WordPress context
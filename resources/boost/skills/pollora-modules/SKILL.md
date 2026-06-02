---
name: pollora-modules
description: Develop Laravel Modules (nwidart) within Pollora projects with auto-discovery of post types, taxonomies, hooks, and more.
---

# Pollora Module Development

## When to use this skill
Use this skill when creating or working with Laravel Modules (nwidart/laravel-modules) in a Pollora project to organize large applications into self-contained feature packages.

## Creating a Module

```bash
php artisan module:make Portfolio
```

## Module Structure

```
Modules/Portfolio/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   ├── Models/
│   │   ├── Project.php          # Eloquent model + #[PostType] attribute
│   │   └── ProjectCategory.php  # #[Taxonomy] attribute
│   ├── Hooks/
│   │   └── ProjectHooks.php     # #[Action] / #[Filter] attributes
│   ├── Schedule/
│   │   └── ProjectSync.php      # #[Schedule] attribute
│   ├── Api/
│   │   └── ProjectAPI.php       # #[WpRestRoute] attribute
│   └── Providers/
│       └── PortfolioServiceProvider.php
├── config/
│   └── config.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── assets/
│   └── views/
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
├── composer.json
├── module.json
└── package.json
```

## Auto-Discovery in Modules

All Pollora attributes work inside modules without any additional configuration:

```php
// Modules/Portfolio/app/Models/Project.php
namespace Modules\Portfolio\Models;

use Pollora\Attributes\PostType\PostType;
use Pollora\Attributes\PostType\PubliclyQueryable;
use Pollora\Attributes\PostType\HasArchive;
use Pollora\Attributes\PostType\ShowInRest;

#[PostType('project')]
#[PubliclyQueryable]
#[HasArchive]
#[ShowInRest]
class Project
{
    public function configuring(\Pollora\Entity\Domain\Model\PostType $postType): void
    {
        $postType->labels([
            'name' => __('Projects', 'portfolio'),
            'singular_name' => __('Project', 'portfolio'),
        ]);
    }
}
```

The discovery system scans module directories automatically — no manual registration needed.

## Module Routes with WordPress Conditions

```php
// Modules/Portfolio/routes/web.php
use Illuminate\Support\Facades\Route;
use Modules\Portfolio\Http\Controllers\ProjectController;

Route::wp('single', [ProjectController::class, 'show']);
Route::wp('archive', [ProjectController::class, 'index']);
```

## Module Management

```bash
php artisan module:make Portfolio          # Create
php artisan module:enable Portfolio        # Enable
php artisan module:disable Portfolio       # Disable
php artisan module:list                    # List all modules
php artisan module:migrate Portfolio       # Run migrations
php artisan module:seed Portfolio          # Run seeders
```

## Module Service Provider

```php
namespace Modules\Portfolio\Providers;

use Illuminate\Support\ServiceProvider;

class PortfolioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            module_path('Portfolio', 'config/config.php'),
            'portfolio'
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('Portfolio', 'database/migrations'));
        $this->loadViewsFrom(module_path('Portfolio', 'resources/views'), 'portfolio');
    }
}
```

## Important Notes

- **Auto-discovery works across modules** — post types, taxonomies, hooks, schedules, and REST routes defined in modules are discovered automatically
- Module views are namespaced: `view('portfolio::project.show')`
- Each module can have its own `composer.json` for module-specific dependencies
- Run `php artisan discovery:clear` after adding attribute-decorated classes in modules
- Modules can define their own Vite config and assets
- Use `module_path('ModuleName', 'relative/path')` to reference module files
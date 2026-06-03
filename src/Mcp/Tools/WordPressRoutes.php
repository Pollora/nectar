<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Tools;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class WordPressRoutes extends Tool
{
    protected string $name = 'wordpress_routes';

    protected string $description = 'List all registered routes including Route::wp() WordPress condition routes, with their URI, methods, action, middleware, and WordPress condition if applicable.';

    public function handle(Request $request): Response
    {
        $routes = Route::getRoutes();
        $result = [];
        $polloraRouteClass = 'Pollora\Route\Infrastructure\Models\Route';

        /** @var LaravelRoute $route */
        foreach ($routes->getRoutes() as $route) {
            $action = $route->getAction();
            $isWordPress = $route instanceof $polloraRouteClass
                && method_exists($route, 'isWordPressRoute')
                && $route->isWordPressRoute(); // @phpstan-ignore-line

            $routeInfo = [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'name' => $route->getName(),
                'middleware' => $route->gatherMiddleware(),
                'is_wordpress_route' => $isWordPress,
            ];

            if (isset($action['controller'])) {
                $routeInfo['controller'] = $action['controller'];
            }

            if ($isWordPress && method_exists($route, 'hasCondition') && $route->hasCondition()) { // @phpstan-ignore-line
                $routeInfo['wp_condition'] = $route->getCondition(); // @phpstan-ignore-line
                $routeInfo['wp_condition_params'] = $route->getConditionParameters(); // @phpstan-ignore-line
            }

            $result[] = $routeInfo;
        }

        /** @var array<int, array<string, mixed>> $result */
        $wpRoutes = collect($result)->where('is_wordpress_route', true)->count();
        $laravelRoutes = collect($result)->where('is_wordpress_route', false)->count();

        return Response::json([
            'summary' => [
                'total' => count($result),
                'wordpress_routes' => $wpRoutes,
                'laravel_routes' => $laravelRoutes,
            ],
            'routes' => $result,
        ]);
    }
}

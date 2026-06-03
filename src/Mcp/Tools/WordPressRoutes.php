<?php

declare(strict_types=1);

namespace Pollora\Nectar\Mcp\Tools;

use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Pollora\Route\Infrastructure\Models\Route as PolloraRoute;

#[IsReadOnly]
class WordPressRoutes extends Tool
{
    protected string $name = 'wordpress_routes';

    protected string $description = 'List all registered routes including Route::wp() WordPress condition routes, with their URI, methods, action, middleware, and WordPress condition if applicable.';

    public function handle(Request $request): Response
    {
        $routes = Route::getRoutes();
        $result = [];

        foreach ($routes as $route) {
            $action = $route->getAction();
            $isWordPress = $route instanceof PolloraRoute && $route->isWordPressRoute();

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

            if ($isWordPress && $route->hasCondition()) {
                $routeInfo['wp_condition'] = $route->getCondition();
                $routeInfo['wp_condition_params'] = $route->getConditionParameters();
            }

            $result[] = $routeInfo;
        }

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

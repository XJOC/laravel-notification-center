<?php

declare(strict_types=1);

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

/**
 * Pre-freeze collision-surface guards. A host app must be able to mount this
 * package without clashing with its own routes. Every package route must:
 *   - live under the configurable `route_prefix` (path collisions), and
 *   - carry a `notification-center.`-namespaced name (name collisions), and
 *   - take its middleware from `admin_middleware` / `user_middleware` config.
 */
if (! function_exists('nc_package_routes')) {
    /**
     * Every route whose action lives in this package's namespace.
     *
     * @return array<int, IlluminateRoute>
     */
    function nc_package_routes(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (IlluminateRoute $route): bool => str_contains(
                $route->getActionName(),
                'Xjoc\\NotificationCenter\\'
            ))
            ->values()
            ->all();
    }
}

it('registers package routes and none escape the configured route_prefix', function (): void {
    $prefix = Config::string('notification-center.route_prefix');
    $routes = nc_package_routes();

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        expect($route->uri())->toStartWith($prefix.'/');
    }
});

it('groups admin routes under /admin and user routes under /user', function (): void {
    $prefix = Config::string('notification-center.route_prefix');

    foreach (nc_package_routes() as $route) {
        $name = (string) $route->getName();

        if (str_starts_with($name, 'notification-center.admin.')) {
            expect($route->uri())->toStartWith($prefix.'/admin/');
        } elseif (str_starts_with($name, 'notification-center.user.')) {
            expect($route->uri())->toStartWith($prefix.'/user/');
        } else {
            throw new RuntimeException("Package route is neither admin nor user: {$name}");
        }
    }
});

it('namespaces every package route name under notification-center.', function (): void {
    $routes = nc_package_routes();

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        expect($route->getName())
            ->not->toBeNull("Package route {$route->uri()} has no name")
            ->and($route->getName())->toStartWith('notification-center.');
    }
});

it('exposes exactly the expected namespaced route names', function (): void {
    $names = collect(nc_package_routes())->map->getName()->sort()->values()->all();

    expect($names)->toBe([
        'notification-center.admin.channels.index',
        'notification-center.admin.event-bindings.destroy',
        'notification-center.admin.settings.index',
        'notification-center.admin.types.dispatch',
        'notification-center.admin.types.event-bindings.index',
        'notification-center.admin.types.event-bindings.store',
        'notification-center.admin.types.index',
        'notification-center.admin.types.store',
        'notification-center.admin.types.templates.index',
        'notification-center.admin.types.templates.update',
        'notification-center.admin.types.update',
        'notification-center.user.preferences.index',
        'notification-center.user.preferences.update',
    ]);
});

it('relocates every route when route_prefix changes', function (): void {
    config()->set('notification-center.route_prefix', 'custom-nc');

    // The route files read `route_prefix` at evaluation time, so re-evaluating
    // them re-registers each route under the new prefix. Inspecting the freshly
    // registered route objects proves the prefix — not a hardcoded path — owns
    // every URL.
    require __DIR__.'/../../routes/admin.php';
    require __DIR__.'/../../routes/user.php';

    $uris = collect(nc_package_routes())->map->uri()->all();

    expect($uris)
        ->toContain('custom-nc/admin/types')
        ->toContain('custom-nc/admin/types/{type}/templates/{channel}')
        ->toContain('custom-nc/admin/event-bindings/{binding}')
        ->toContain('custom-nc/admin/channels')
        ->toContain('custom-nc/user/preferences')
        ->toContain('custom-nc/user/preferences/{type}/{channel}');
});

it('builds admin and user middleware from config, not hardcoded values', function (): void {
    config()->set('notification-center.admin_middleware', ['auth:custom-admin', 'role:nc-admin']);
    config()->set('notification-center.user_middleware', ['auth:custom-user']);

    require __DIR__.'/../../routes/admin.php';
    require __DIR__.'/../../routes/user.php';

    // Re-requiring leaves the original (empty-middleware) routes registered too,
    // so gather every middleware attached to the route(s) of a given name.
    $middlewareFor = function (string $name): array {
        $middleware = [];

        foreach (nc_package_routes() as $route) {
            if ($route->getName() === $name) {
                $middleware = array_merge($middleware, $route->gatherMiddleware());
            }
        }

        return $middleware;
    };

    $adminMiddleware = $middlewareFor('notification-center.admin.types.index');
    $userMiddleware = $middlewareFor('notification-center.user.preferences.index');

    // Admin routes carry admin_middleware (from config) plus SubstituteBindings.
    expect($adminMiddleware)
        ->toContain('auth:custom-admin')
        ->toContain('role:nc-admin')
        ->toContain(SubstituteBindings::class);

    // User routes carry user_middleware (from config) and never the admin set.
    expect($userMiddleware)
        ->toContain('auth:custom-user')
        ->toContain(SubstituteBindings::class);

    expect(in_array('auth:custom-admin', $userMiddleware, true))->toBeFalse();
    expect(in_array('role:nc-admin', $userMiddleware, true))->toBeFalse();
});

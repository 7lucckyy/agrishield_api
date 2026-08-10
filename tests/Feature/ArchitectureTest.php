<?php

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

test('each versioned API controller is a single action controller', function () {
    $controllerRoutes = collect(RouteFacade::getRoutes()->getRoutes())
        ->filter(fn (Route $route): bool => str_starts_with($route->uri(), 'api/v1/'))
        ->filter(fn (Route $route): bool => str_starts_with(
            $route->getActionName(),
            'App\\Http\\Controllers\\Api\\V1\\',
        ));

    expect($controllerRoutes)->not->toBeEmpty();

    $controllerRoutes->each(function (Route $route): void {
        $controller = new ReflectionClass($route->getActionName());
        $publicActionMethods = collect($controller->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(fn (ReflectionMethod $method): bool => $method->getDeclaringClass()->getName() === $controller->getName())
            ->reject(fn (ReflectionMethod $method): bool => $method->isConstructor())
            ->pluck('name')
            ->values()
            ->all();

        expect($publicActionMethods)->toBe(['__invoke']);
    });
});

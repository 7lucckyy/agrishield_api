<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

test('every API endpoint is routable without a server error', function () {
    $apiRoutes = collect(RouteFacade::getRoutes()->getRoutes())
        ->filter(fn (Route $route): bool => str_starts_with($route->uri(), 'api/v1'))
        ->values();

    expect($apiRoutes)->not->toBeEmpty();

    foreach ($apiRoutes as $route) {
        $method = collect($route->methods())
            ->first(fn (string $candidate): bool => $candidate !== 'HEAD');
        $uri = '/'.preg_replace('/\{[^}]+}/', '00000000-0000-4000-8000-000000000000', $route->uri());

        expect($method)->toBeString();

        $response = $this->json($method, $uri, [], ['Accept' => 'application/json']);

        expect(
            $response->getStatusCode(),
            "{$method} {$route->uri()} returned a server error.",
        )->toBeLessThan(500);
    }
});

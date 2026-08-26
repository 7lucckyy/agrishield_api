<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;

test('every OpenAPI operation has a registered route', function () {
    $specification = Yaml::parseFile(base_path('agrishield-docs/openapi.yaml'));
    $registered = collect(Route::getRoutes()->getRoutes())->flatMap(function ($route): array {
        $path = preg_replace('/\{[^}]+}/', '{param}', preg_replace('#^api/v1#', '', $route->uri()));

        return collect($route->methods())
            ->reject(fn (string $method): bool => $method === 'HEAD')
            ->map(fn (string $method): string => mb_strtolower($method).' '.$path)
            ->all();
    })->all();

    $missing = [];
    foreach ($specification['paths'] as $path => $operations) {
        $normalisedPath = preg_replace('/\{[^}]+}/', '{param}', $path);
        foreach (array_keys($operations) as $method) {
            if (in_array($method, ['parameters', 'summary', 'description'], true)) {
                continue;
            }

            $operation = mb_strtolower($method).' '.$normalisedPath;
            if (! in_array($operation, $registered, true)) {
                $missing[] = $operation;
            }
        }
    }

    expect($missing)->toBe([]);
});

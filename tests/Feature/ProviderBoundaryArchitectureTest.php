<?php

declare(strict_types=1);

use App\Integrations\Contracts\FarmingInsightsProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;

test('provider contract never returns arrays or http responses', function () {
    $contract = new ReflectionClass(FarmingInsightsProvider::class);

    foreach ($contract->getMethods() as $method) {
        $returnType = $method->getReturnType();

        expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
        if ($returnType instanceof ReflectionNamedType) {
            expect($returnType->getName())
                ->not->toBe('array')
                ->not->toContain('Illuminate\\Http');
        }
    }
});

test('provider response array access is confined to the integrations boundary', function () {
    $violations = collect(File::allFiles(app_path()))
        ->reject(fn (SplFileInfo $file): bool => str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'Integrations'.DIRECTORY_SEPARATOR))
        ->filter(function (SplFileInfo $file): bool {
            $contents = $file->getContents();

            return preg_match('/\$(response|providerPayload|providerData)\s*\[/', $contents) === 1;
        })
        ->map(fn (SplFileInfo $file): string => $file->getPathname())
        ->values()
        ->all();

    expect($violations)->toBe([]);
});

arch('http client types stay inside provider adapters')
    ->expect(['App\Actions', 'App\Jobs', 'App\Http', 'App\Models'])
    ->not->toUse([
        PendingRequest::class,
        Response::class,
    ]);

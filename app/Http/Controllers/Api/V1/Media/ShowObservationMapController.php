<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Models\SatelliteObservation;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;

final class ShowObservationMapController extends Controller
{
    public function __invoke(SatelliteObservation $observation): Response
    {
        Gate::authorize('view', $observation->farm);
        abort_if($observation->map_url === null, 404);

        $allowedHost = parse_url((string) config('farming.providers.satyukt.base_url'), PHP_URL_HOST);
        $mapHost = parse_url($observation->map_url, PHP_URL_HOST);
        abort_unless(is_string($allowedHost) && $allowedHost !== '' && hash_equals($allowedHost, (string) $mapHost), 404);

        $providerResponse = Http::connectTimeout(5)->timeout(20)->get($observation->map_url)->throw();

        return response($providerResponse->body(), 200, [
            'Content-Type' => $providerResponse->header('Content-Type'),
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}

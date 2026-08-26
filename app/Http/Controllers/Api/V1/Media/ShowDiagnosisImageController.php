<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Media;

use App\Http\Controllers\Controller;
use App\Models\DiagnosisRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ShowDiagnosisImageController extends Controller
{
    public function __invoke(DiagnosisRequest $diagnosis): StreamedResponse
    {
        Gate::authorize('view', $diagnosis->farm);

        return Storage::disk($diagnosis->image_disk)->response(
            $diagnosis->image_path,
            headers: ['Content-Type' => $diagnosis->image_mime, 'Cache-Control' => 'private, no-store'],
        );
    }
}

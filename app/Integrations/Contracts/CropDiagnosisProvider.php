<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

use App\DTOs\Provider\DiagnosisResult;
use App\DTOs\Provider\DiagnosisSubmission;
use App\Models\DiagnosisRequest;

interface CropDiagnosisProvider
{
    public function name(): string;

    public function submitDiagnosis(DiagnosisRequest $request, string $idempotencyKey): DiagnosisSubmission;

    public function fetchDiagnosisResult(DiagnosisRequest $request): DiagnosisResult;
}

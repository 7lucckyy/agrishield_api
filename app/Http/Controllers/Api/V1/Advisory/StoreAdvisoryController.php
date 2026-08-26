<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Advisory;

use App\Actions\Audit\RecordAuditLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Insight\StoreAdvisoryRequest;
use App\Http\Resources\Api\V1\AdvisoryResource;
use App\Models\Advisory;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class StoreAdvisoryController extends Controller
{
    public function __invoke(StoreAdvisoryRequest $request, Farm $farm, RecordAuditLog $recordAuditLog): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $data['source'] = 'manual';
        $data['observed_at'] = now();
        $data['dedupe_key'] = hash('sha256', implode('|', [$farm->getKey(), $data['type'], now()->toISOString(), $data['title'], 'manual']));
        $advisory = new Advisory;
        $advisory->fill($data);
        $advisory->farm()->associate($farm);
        $advisory->issuedBy()->associate($user);
        $advisory->save();
        $recordAuditLog->execute('advisory.created', $advisory, ['after' => $advisory->only(['type', 'title', 'severity'])], $user);
        $advisory->setRelation('acknowledgements', collect());

        return (new AdvisoryResource($advisory))->response()->setStatusCode(201);
    }
}

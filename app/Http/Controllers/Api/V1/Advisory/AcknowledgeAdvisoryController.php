<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Advisory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Insight\AcknowledgeAdvisoryRequest;
use App\Http\Resources\Api\V1\AdvisoryResource;
use App\Models\Advisory;
use App\Models\AdvisoryAcknowledgement;
use App\Models\Farm;
use App\Models\User;

final class AcknowledgeAdvisoryController extends Controller
{
    public function __invoke(AcknowledgeAdvisoryRequest $request, Farm $farm, Advisory $advisory): AdvisoryResource
    {
        /** @var User $user */
        $user = $request->user();
        $acknowledgement = AdvisoryAcknowledgement::query()->firstOrNew([
            'advisory_id' => $advisory->getKey(),
            'user_id' => $user->getKey(),
        ]);
        $acknowledgement->advisory()->associate($advisory);
        $acknowledgement->user()->associate($user);
        $acknowledgement->fill([
            'read_at' => $request->boolean('read') ? ($acknowledgement->read_at ?? now()) : null,
            'acted_at' => $request->boolean('acted') ? ($acknowledgement->acted_at ?? now()) : null,
            'feedback' => $request->validated('feedback'),
        ])->save();
        $advisory->setRelation('acknowledgements', collect([$acknowledgement]));

        return new AdvisoryResource($advisory);
    }
}

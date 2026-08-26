<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Advisory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Insight\ListAdvisoryRequest;
use App\Http\Resources\Api\V1\AdvisoryResource;
use App\Models\Advisory;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ListAdvisoryController extends Controller
{
    public function __invoke(ListAdvisoryRequest $request, Farm $farm): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $query = Advisory::query()
            ->whereBelongsTo($farm)
            ->with(['acknowledgements' => function (Relation $relation) use ($user): void {
                $relation->getQuery()->whereBelongsTo($user);
            }]);
        $types = array_filter(explode(',', (string) $request->validated('filter.type', '')));
        $severities = array_filter(explode(',', (string) $request->validated('filter.severity', '')));
        $query->when($types !== [], fn (Builder $builder): Builder => $builder->whereIn('type', $types));
        $query->when($severities !== [], fn (Builder $builder): Builder => $builder->whereIn('severity', $severities));
        $query->when($request->validated('filter.crop_cycle_id'), fn (Builder $builder, mixed $id): Builder => $builder->where('farm_crop_cycle_id', $id));
        $query->when($request->boolean('filter.active'), fn (Builder $builder): Builder => $builder
            ->where(fn (Builder $validFrom): Builder => $validFrom->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where(fn (Builder $validUntil): Builder => $validUntil->whereNull('valid_until')->orWhere('valid_until', '>=', now())));
        $query->when($request->boolean('filter.unread'), fn (Builder $builder): Builder => $builder->whereDoesntHave(
            'acknowledgements',
            fn (Builder $acknowledgements): Builder => $acknowledgements->whereBelongsTo($user)->whereNotNull('read_at'),
        ));
        $query->when($request->validated('filter.from'), fn (Builder $builder, mixed $date): Builder => $builder->whereDate('observed_at', '>=', $date));
        $query->when($request->validated('filter.to'), fn (Builder $builder, mixed $date): Builder => $builder->whereDate('observed_at', '<=', $date));
        $paginator = $query->latest('observed_at')->paginate((int) $request->validated('per_page', 25));

        return AdvisoryResource::collection($paginator)->additional(['meta' => [
            'counts_by_type' => Advisory::query()->whereBelongsTo($farm)->selectRaw('type, COUNT(*) AS aggregate')->groupBy('type')->pluck('aggregate', 'type'),
            'counts_by_severity' => Advisory::query()->whereBelongsTo($farm)->selectRaw('severity, COUNT(*) AS aggregate')->groupBy('severity')->pluck('aggregate', 'severity'),
            'unread' => Advisory::query()->whereBelongsTo($farm)->whereDoesntHave('acknowledgements', fn (Builder $builder): Builder => $builder->whereBelongsTo($user)->whereNotNull('read_at'))->count(),
        ]]);
    }
}

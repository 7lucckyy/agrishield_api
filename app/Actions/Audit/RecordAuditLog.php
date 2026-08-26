<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Integrations\Support\PayloadScrubber;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final readonly class RecordAuditLog
{
    public function __construct(private PayloadScrubber $payloadScrubber, private Request $request) {}

    /** @param array<string, mixed> $changes */
    public function execute(string $action, ?Model $auditable, array $changes, ?User $actor = null): AuditLog
    {
        $auditLog = new AuditLog;
        $auditLog->fill([
            'action' => $action,
            'changes' => $this->payloadScrubber->scrub($changes),
            'context' => ['request_id' => $this->request->attributes->get('request_id')],
            'ip_address' => $this->request->ip(),
        ]);
        $auditLog->actor()->associate($actor ?? $this->request->user());
        $auditLog->auditable()->associate($auditable);
        $auditLog->save();

        return $auditLog;
    }
}

@extends('layouts.admin', ['area' => 'organization', 'organization' => $organization])
@section('title', 'Asset Access')
@section('eyebrow', $organization->name)
@section('page-title', 'Asset Access')

@section('content')
<section class="page-lead compact asset-access-lead">
    <div>
        <p class="eyebrow"><span></span> Productive asset finance</p>
        <h1>Asset Access</h1>
        <p>Prepare crop-asset applications, retain farmer consent and track the handoff to a regulated finance partner.</p>
    </div>
    <div class="asset-access-boundary"><strong>AgriShield does not approve or issue finance.</strong><span>Every credit decision, disbursement and collection remains with the selected bank.</span></div>
</section>

@if($errors->any())
<div class="form-error asset-access-errors" role="alert"><strong>Review the application details.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<section class="asset-partner-grid" aria-label="Proposed finance partners">
    @foreach($products->groupBy('finance_partner_id') as $partnerProducts)
        @php($partner = $partnerProducts->first()->financePartner)
        <article>
            <header><span>{{ str($partner->type->value)->headline() }}</span><b>{{ str($partner->metadata['relationship_status'] ?? 'proposed')->headline() }}</b></header>
            <h2>{{ $partner->name }}</h2>
            <p>{{ $partner->financing_model }}</p>
            <ul>@foreach($partnerProducts as $product)<li>{{ $product->name }}</li>@endforeach</ul>
            <small>Programme terms remain subject to a signed agreement and bank approval.</small>
        </article>
    @endforeach
</section>

@if($canManage)
<section class="panel asset-application-panel">
    <div class="panel-head"><div><span>New application</span><h2>Prepare a bank handoff</h2></div><small>Consent required</small></div>
    <form method="POST" action="{{ route('organization.asset-finance.store', $organization) }}" class="asset-application-form">
        @csrf
        <label><span>Registered farm</span><select name="farm_id" required><option value="">Select a farm</option>@foreach($farms as $farm)<option value="{{ $farm->uuid }}" @selected(old('farm_id') === $farm->uuid)>{{ $farm->name }}{{ $farm->activeCropCycle?->crop ? ' · '.$farm->activeCropCycle->crop->name : '' }}</option>@endforeach</select></label>
        <label><span>Finance programme</span><select name="asset_finance_product_id" required><option value="">Select an asset programme</option>@foreach($products as $product)<option value="{{ $product->getKey() }}" @selected((int) old('asset_finance_product_id') === $product->getKey())>{{ $product->financePartner->name }} · {{ $product->name }}</option>@endforeach</select></label>
        <label><span>Requested amount in naira</span><input type="number" name="requested_amount" min="1" step="0.01" value="{{ old('requested_amount') }}" required></label>
        <label><span>Quantity</span><input type="number" name="quantity" min="1" max="100" value="{{ old('quantity', 1) }}" required></label>
        <label class="asset-purpose"><span>Purpose and expected farm benefit</span><textarea name="purpose" rows="4" minlength="20" maxlength="2000" required>{{ old('purpose') }}</textarea></label>
        <label class="asset-consent"><input type="checkbox" name="consent" value="1" required><span>I confirm that the farmer has agreed to share this application and the linked farm and crop information with the selected finance partner for eligibility and underwriting checks.</span></label>
        <div class="asset-form-footer"><p>Submitting records consent and creates a handoff record. It does not guarantee approval.</p><button class="admin-primary-button" type="submit">Record application <span>→</span></button></div>
    </form>
</section>
@endif

<section class="panel">
    <div class="panel-head"><div><span>Partner handoffs</span><h2>Applications</h2></div><small>{{ $applications->total() }} records</small></div>
    <div class="asset-application-list">
        @forelse($applications as $application)
        <article>
            <div class="asset-application-summary">
                <span class="status-pill status-{{ $application->status->value }}">{{ str($application->status->value)->headline() }}</span>
                <small>{{ $application->product->financePartner->name }} · {{ $application->product->name }}</small>
                <h3>{{ $application->farm->name }}</h3>
                <p>{{ $application->purpose }}</p>
                <dl><div><dt>Requested</dt><dd>₦{{ number_format((float) $application->requested_amount) }}</dd></div><div><dt>Consent</dt><dd>{{ $application->consented_at->format('d M Y') }}</dd></div><div><dt>Repayment</dt><dd>{{ str($application->repayment_status->value)->headline() }}</dd></div></dl>
            </div>
            <div class="asset-application-operations">
                <span>Bank reference</span><strong>{{ $application->partner_reference ?: 'Pending handoff' }}</strong>
                @if($canManage && count($application->status->allowedTransitions()) > 0)
                <form method="POST" action="{{ route('organization.asset-finance.update', [$organization, $application]) }}">
                    @csrf @method('PATCH')
                    <label><span>Next verified status</span><select name="status" required>@foreach($application->status->allowedTransitions() as $status)<option value="{{ $status->value }}">{{ str($status->value)->headline() }}</option>@endforeach</select></label>
                    <label><span>Partner reference</span><input name="partner_reference" value="{{ $application->partner_reference }}" maxlength="191"></label>
                    <label><span>Repayment visibility</span><select name="repayment_status"><option value="">No update</option>@foreach($repaymentStatuses as $status)<option value="{{ $status->value }}" @selected($application->repayment_status === $status)>{{ str($status->value)->headline() }}</option>@endforeach</select></label>
                    <label><span>Operational note</span><textarea name="decision_note" rows="2" maxlength="2000"></textarea></label>
                    <button type="submit">Update verified status</button>
                </form>
                @else
                <p class="asset-application-closed">No further workflow transition is available.</p>
                @endif
            </div>
        </article>
        @empty
        <div class="empty-state"><strong>No asset applications yet</strong><span>Approved organisation administrators can record the first farmer-authorised application above.</span></div>
        @endforelse
    </div>
    <div class="pagination">{{ $applications->links() }}</div>
</section>
@endsection

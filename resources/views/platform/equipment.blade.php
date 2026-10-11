@extends('layouts.admin', ['area' => 'platform'])
@section('title', 'Equipment requests')
@section('eyebrow', 'Equipment desk')
@section('page-title', 'Equipment requests')

@section('content')
<section class="page-lead compact"><div><p class="eyebrow"><span></span> Farmer requests</p><h1>Equipment desk</h1><p>Review purchase and leasing requests from registered farms. A status update is an internal operational record, not a loan contract.</p></div></section>

@if(session('status'))<div class="status-message" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="form-error" role="alert"><strong>Review the update.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<section class="panel">
    <div class="panel-head"><div><span>Requests</span><h2>Review queue</h2></div><small>{{ $applications->total() }} records</small></div>
    <form class="search-form" method="GET" action="{{ route('platform.equipment.index') }}">
        <label for="equipment-status">Filter by status</label>
        <select id="equipment-status" name="status"><option value="">All statuses</option>@foreach(\App\Enums\AssetFinanceStatus::cases() as $item)<option value="{{ $item->value }}" @selected($status === $item->value)>{{ str($item->value)->headline() }}</option>@endforeach</select>
        <button type="submit">Filter</button>
    </form>
    <div class="asset-application-list">
        @forelse($applications as $application)
        <article>
            <div class="asset-application-summary">
                <span class="status-pill status-{{ $application->status->value }}">{{ str($application->status->value)->headline() }}</span>
                <small>{{ $application->product->name }} · {{ $application->acquisition_mode === \App\Enums\EquipmentAcquisitionMode::Lease ? 'Lease' : 'Purchase' }}</small>
                <h3>{{ $application->farm->name }}</h3>
                <p>{{ $application->purpose }}</p>
                <dl>
                    <div><dt>Farmer</dt><dd>{{ $application->applicant->name }} · {{ $application->applicant->phone ?: 'No phone' }}</dd></div>
                    <div><dt>Quantity</dt><dd>{{ $application->quantity }}</dd></div>
                    <div><dt>Farmer budget</dt><dd>₦{{ number_format((float) $application->requested_amount) }}</dd></div>
                    <div><dt>Consent</dt><dd>{{ $application->consented_at->format('d M Y') }}</dd></div>
                </dl>
                @if($application->decision_note)<p><strong>Latest note:</strong> {{ $application->decision_note }}</p>@endif
            </div>
            <div class="asset-application-operations">
                <strong>Record review</strong>
                @php($nextStatuses = array_filter($application->status->allowedTransitions(), fn ($next) => in_array($next, [\App\Enums\AssetFinanceStatus::UnderReview, \App\Enums\AssetFinanceStatus::Approved, \App\Enums\AssetFinanceStatus::Declined, \App\Enums\AssetFinanceStatus::AssetOrdered, \App\Enums\AssetFinanceStatus::Delivered], true)))
                @if($nextStatuses)
                <form method="POST" action="{{ route('platform.equipment.update', $application) }}">
                    @csrf @method('PATCH')
                    <label><span>Next status</span><select name="status" required>@foreach($nextStatuses as $next)<option value="{{ $next->value }}">{{ str($next->value)->headline() }}</option>@endforeach</select></label>
                    <label><span>Decision or verification note</span><textarea name="decision_note" rows="3" minlength="20" maxlength="2000" required></textarea></label>
                    @if(in_array(\App\Enums\AssetFinanceStatus::Delivered, $nextStatuses, true))<label class="asset-consent"><input type="checkbox" name="delivery_confirmed" value="1"><span>I verified that the equipment was delivered to this farmer. Required when recording delivery.</span></label>@endif
                    <button type="submit">Save status</button>
                </form>
                @else<p>No further desk transition is available.</p>@endif
            </div>
        </article>
        @empty<div class="empty-state"><strong>No equipment requests</strong><span>Requests from the farmer app appear here when the equipment desk is enabled.</span></div>@endforelse
    </div>
    <div class="pagination">{{ $applications->links() }}</div>
</section>
<section class="panel">
    <div class="panel-head"><div><span>Farmer catalogue</span><h2>Equipment availability</h2></div><small>{{ $products->count() }} tools</small></div>
    <p>Changes to availability and request type affect which tools farmers can request. Farm size and irrigation matching remain in effect.</p>
    <div class="asset-application-list">
        @foreach($products as $product)
        <article>
            <div class="asset-application-summary"><h3>{{ $product->name }}</h3><p>{{ $product->metadata['description'] ?? '' }}</p><small>Farm sizes: {{ implode(', ', $product->metadata['farm_size_tiers'] ?? []) }}</small></div>
            <div class="asset-application-operations">
                <form method="POST" action="{{ route('platform.equipment.products.update', $product) }}">
                    @csrf @method('PATCH')
                    <label><span>Availability</span><select name="is_active"><option value="1" @selected($product->is_active)>Available</option><option value="0" @selected(! $product->is_active)>Paused</option></select></label>
                    <label class="asset-consent"><input type="checkbox" name="acquisition_options[]" value="finance_purchase" @checked(in_array('finance_purchase', $product->metadata['acquisition_options'] ?? [], true))><span>Purchase request</span></label>
                    <label class="asset-consent"><input type="checkbox" name="acquisition_options[]" value="lease" @checked(in_array('lease', $product->metadata['acquisition_options'] ?? [], true))><span>Lease request</span></label>
                    <button type="submit">Save equipment</button>
                </form>
            </div>
        </article>
        @endforeach
    </div>
</section>
@endsection

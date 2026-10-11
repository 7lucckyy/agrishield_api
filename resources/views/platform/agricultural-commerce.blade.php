@extends('layouts.admin', ['area' => 'platform'])
@section('title', 'Markets and agricultural services')
@section('eyebrow', 'Partner operations')
@section('page-title', 'Markets and services')

@section('content')
<section class="page-lead compact"><div><p class="eyebrow"><span></span> Verified partner network</p><h1>Markets and agricultural services</h1><p>Verify partners, publish offers, and manage farmer requests. Published prices and availability remain subject to partner confirmation.</p></div></section>

@if(session('status'))<div class="status-message" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="form-error" role="alert"><strong>Review the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<section class="panel">
    <div class="panel-head"><div><span>Onboarding</span><h2>Add a verified partner</h2></div><small>{{ $partners->count() }} partners</small></div>
    <form class="settings-form" method="POST" action="{{ route('platform.commerce.partners.store') }}">@csrf
        <div class="form-grid"><label><span>Partner name</span><input name="name" required maxlength="255" value="{{ old('name') }}"></label><label><span>Partner type</span><select name="type" required>@foreach(\App\Enums\AgriculturalPartnerType::cases() as $type)<option value="{{ $type->value }}">{{ str($type->value)->headline() }}</option>@endforeach</select></label><label><span>Phone</span><input name="contact_phone" maxlength="32" value="{{ old('contact_phone') }}"></label><label><span>Email</span><input type="email" name="contact_email" value="{{ old('contact_email') }}"></label><label><span>State</span><input name="state" value="{{ old('state') }}"></label><label><span>Locality</span><input name="locality" value="{{ old('locality') }}"></label></div>
        <label><span>Verification evidence and reviewer note</span><textarea name="verification_note" rows="3" minlength="20" maxlength="1000" required>{{ old('verification_note') }}</textarea></label>
        <button type="submit">Verify and add partner</button>
    </form>
</section>

<section class="panel">
    <div class="panel-head"><div><span>Farmer catalogue</span><h2>Publish an offer</h2></div><small>{{ $offers->count() }} offers</small></div>
    @if($partners->whereNotNull('verified_at')->isEmpty())<div class="empty-state"><strong>Add a verified partner first</strong><span>An offer cannot be published without a reviewed provider.</span></div>@else
    <form class="settings-form" method="POST" action="{{ route('platform.commerce.offers.store') }}">@csrf
        <div class="form-grid"><label><span>Partner</span><select name="agricultural_partner_id" required>@foreach($partners->whereNotNull('verified_at')->where('is_active', true) as $partner)<option value="{{ $partner->id }}">{{ $partner->name }}</option>@endforeach</select></label><label><span>Service</span><select name="category" required>@foreach(\App\Enums\AgriculturalOfferCategory::cases() as $category)<option value="{{ $category->value }}">{{ str($category->value)->headline() }}</option>@endforeach</select></label><label><span>Crop (optional)</span><select name="crop_id"><option value="">All crops</option>@foreach($crops as $crop)<option value="{{ $crop->id }}">{{ $crop->name }}</option>@endforeach</select></label><label><span>Title</span><input name="title" required maxlength="255"></label><label><span>Price type</span><select name="pricing_model"><option value="fixed">Fixed</option><option value="indicative">Indicative</option><option value="quote">Partner quote</option></select></label><label><span>Unit price (NGN)</span><input type="number" name="unit_price" min="0" step="0.01"></label><label><span>Unit</span><input name="unit" placeholder="100kg bag, hectare, day"></label><label><span>Minimum quantity</span><input type="number" name="minimum_quantity" min="0.001" step="0.001"></label><label><span>Available quantity</span><input type="number" name="available_quantity" min="0" step="0.001"></label><label><span>State (blank means national)</span><input name="state"></label><label><span>Locality</span><input name="locality"></label><label><span>Closes on</span><input type="date" name="ends_at"></label></div>
        <label><span>Description</span><textarea name="description" rows="3" maxlength="2000"></textarea></label><label><span>Requirements, one per line</span><textarea name="requirements_text" rows="3" maxlength="2000"></textarea></label>
        <button type="submit">Publish farmer offer</button>
    </form>@endif
</section>

<section class="panel">
    <div class="panel-head"><div><span>Farmer requests</span><h2>Partner review queue</h2></div><small>{{ $serviceRequests->total() }} records</small></div>
    <form class="search-form" method="GET" action="{{ route('platform.commerce.index') }}"><label for="service-status">Filter by status</label><select id="service-status" name="status"><option value="">All statuses</option>@foreach(\App\Enums\AgriculturalServiceRequestStatus::cases() as $item)<option value="{{ $item->value }}" @selected($status === $item->value)>{{ str($item->value)->headline() }}</option>@endforeach</select><button type="submit">Filter</button></form>
    <div class="asset-application-list">
        @forelse($serviceRequests as $item)
            <article>
                <div class="asset-application-summary">
                    <span class="status-pill">{{ str($item->status->value)->headline() }}</span>
                    <small>{{ str($item->request_type->value)->headline() }} · {{ $item->offer->partner->name }}</small>
                    <h3>{{ $item->offer->title }}</h3>
                    <p>{{ $item->purpose ?: 'No additional note.' }}</p>
                    <dl>
                        <div><dt>Farmer</dt><dd>{{ $item->user->name }} · {{ $item->user->phone ?: 'No phone' }}</dd></div>
                        <div><dt>Farm</dt><dd>{{ $item->farm->name }} · {{ $item->farm->state ?: 'State not recorded' }}</dd></div>
                        <div><dt>Quantity</dt><dd>{{ $item->quantity ?: '—' }} {{ $item->unit }}</dd></div>
                        <div><dt>Budget</dt><dd>{{ $item->budget_amount ? '₦'.number_format((float) $item->budget_amount) : 'Not stated' }}</dd></div>
                    </dl>
                    @if($item->decision_note)<p><strong>Latest update:</strong> {{ $item->decision_note }}</p>@endif
                </div>
                <div class="asset-application-operations">
                    <strong>Record partner response</strong>
                    @if($item->status->allowedTransitions() === [])
                        <p>This request is closed. Its audit history remains available in the record.</p>
                    @else
                        <form method="POST" action="{{ route('platform.commerce.requests.update', $item) }}">
                            @csrf
                            @method('PATCH')
                            <label><span>Next status</span><select name="status" required>@foreach($item->status->allowedTransitions() as $next)<option value="{{ $next->value }}">{{ str($next->value)->headline() }}</option>@endforeach</select></label>
                            <label><span>Farmer-facing update</span><textarea name="decision_note" rows="3" minlength="20" maxlength="2000" required></textarea></label>
                            <button type="submit">Save request update</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="empty-state"><strong>No farmer requests</strong><span>Requests from Farm services will appear here.</span></div>
        @endforelse
    </div>
    <div class="pagination">{{ $serviceRequests->links() }}</div>
</section>
@endsection

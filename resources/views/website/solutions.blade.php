@extends('layouts.marketing')
@section('title', 'Solutions | AgriShield AI Ltd')
@section('content')
<section class="inner-hero"><p class="eyebrow light"><span></span> Solutions</p><h1>One intelligence layer.<br>Many better decisions.</h1><p>Modular agricultural systems that move confidently from focused pilots to statewide delivery.</p></section>
@php($solutions = [
['registries','Farmer Census & Digital Registry','Verified, georeferenced farmer and household profiles for accountable planning.',['Offline-first registration','Identity and duplicate controls','Household and value-chain profiling']],
['livestock','Livestock Information Management','Animal records, health surveillance, movement patterns and service delivery in one system.',['Traceability and ownership','Disease surveillance','Corridor and market intelligence']],
['land','Soil Survey & Land Intelligence','Soil observations and land data for productive, sustainable decisions.',['Soil survey workflows','Crop suitability analysis','Restoration monitoring']],
['gis','Agricultural GIS Mapping','Farm boundaries, earth imagery and infrastructure as a clear spatial decision layer.',['Farm and asset mapping','Satellite change detection','Service coverage analysis']],
['food-security','Food Security Intelligence','Production, market, climate and vulnerability indicators brought together for earlier action.',['Multi-source dashboards','Threshold-based warning','Scenario analysis']],
['risk','Climate Risk Monitoring','Climate signals translated into practical monitoring and response workflows.',['Forecast integration','Risk thresholds','Field-ready advisories']],
['meal','Agricultural MEAL Systems','Monitoring, evaluation, accountability and learning embedded in programme delivery.',['Indicator frameworks','Field verification','Donor-ready reporting']],
['ai','AI & Predictive Analytics','Responsible models that forecast risk, reveal patterns and focus human attention.',['Risk and yield forecasting','Anomaly detection','Explainable support']],
['markets','Market Access & Trade Linkages','Trusted linkages that help farmers aggregate supply and fulfil demand.',['Verified buyer networks','Price intelligence','Order fulfilment']],
['clusters','Farmer Clustering & Cooperatives','Practical production clusters for extension, inputs, aggregation and bargaining power.',['Geospatial clustering','Group production planning','Cooperative tools']],
['finance','Input Financing & Credit Enablement','Verified profiles and production histories supporting responsible seasonal finance.',['Eligibility profiles','Digital vouchers','Portfolio monitoring']],
['mechanisation','Mechanisation & Equipment Access','Transparent scheduling for tractors, irrigation systems and equipment networks.',['Equipment booking','Provider coordination','Utilisation tracking']],
])
<section class="solution-catalog section-pad">@foreach($solutions as $index => [$id,$title,$description,$features])<article id="{{ $id }}"><span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span><div><h2>{{ $title }}</h2><p>{{ $description }}</p></div><ul>@foreach($features as $feature)<li>{{ $feature }}</li>@endforeach</ul></article>@endforeach</section>
@include('website.partials.cta')
@endsection

@extends('layouts.marketing')
@section('title', 'Partners | AgriShield AI Ltd')
@section('content')
<section class="inner-hero"><p class="eyebrow light"><span></span> Partnerships</p><h1>Complex challenges<br>need shared resolve.</h1><p>Our platforms are designed for collaboration across government, development and agricultural institutions.</p></section>
@php($ecosystem = [['G','Grow Green Farms and Supply Ltd','Partner'],['F','FAO','Development agency'],['W','WFP','Humanitarian organization'],['I','IFAD','Development finance'],['N','NEDC','Government institution'],['F','FMAFS','Government institution'],['A','ACReSAL','Climate resilience'],['F','FADAMA','Agricultural programme'],['W','World Bank','Development finance'],['A','African Development Bank','Development finance'],['U','USAID','Development agency'],['G','GIZ','Development agency']])
<section class="partner-section section-pad"><div class="partner-grid">@foreach($ecosystem as [$initial,$name,$type])<article><span>{{ $initial }}</span><small>{{ $type }}</small><strong>{{ $name }}</strong></article>@endforeach</div><p class="partner-disclaimer">Organizations shown represent the collaboration ecosystem our solutions are designed to support. Display does not imply endorsement or an existing partnership unless formally announced.</p></section>
<section class="collaboration section-pad"><div><p class="eyebrow"><span></span> Ways to collaborate</p><h2>Start with a shared outcome.</h2></div><ul><li>Digital public infrastructure and registries</li><li>Monitoring, evaluation and learning systems</li><li>Food security and early-warning intelligence</li><li>GIS, remote sensing and field verification</li></ul></section>
@include('website.partials.cta')
@endsection

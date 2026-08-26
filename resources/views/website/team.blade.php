@extends('layouts.marketing')
@section('title', 'Team | AgriShield AI Ltd')
@section('content')
<section class="inner-hero"><p class="eyebrow light"><span></span> Our team</p><h1>Multidisciplinary<br>by design.</h1><p>Agriculture, technology, geospatial science and programme delivery—working as one team.</p></section>
@php($team = [['DA','Dr. Amina Bello','Chief Executive Officer','Agricultural systems strategist focused on inclusive digital transformation and regional food security.'],['EM','Engr. Musa Ibrahim','Director, Data & AI','Data architect building responsible intelligence platforms for public programmes and field operations.'],['HA','Hauwa Abubakar','Head of Programmes','Programme leader connecting government priorities, development partners and farming communities.'],['YD','Yakubu Daniel','Lead, GIS & Remote Sensing','Geospatial specialist translating earth observation and field data into actionable regional insight.']])
<section class="team-grid section-pad">@foreach($team as [$initials,$name,$role,$bio])<article><div class="team-portrait"><span>{{ $initials }}</span><i></i></div><small>{{ $role }}</small><h2>{{ $name }}</h2><p>{{ $bio }}</p></article>@endforeach</section>
@include('website.partials.cta')
@endsection

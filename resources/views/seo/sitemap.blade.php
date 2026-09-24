{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($routes as $publicRoute)
    <url>
        <loc>{{ route($publicRoute['name']) }}</loc>
        <changefreq>{{ $publicRoute['changeFrequency'] }}</changefreq>
        <priority>{{ $publicRoute['priority'] }}</priority>
    </url>
@endforeach
</urlset>

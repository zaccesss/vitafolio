{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ route('home') }}</loc><changefreq>daily</changefreq></url>
@foreach (['about', 'privacy', 'terms', 'accessibility', 'cookies'] as $page)
    <url><loc>{{ route($page) }}</loc><changefreq>monthly</changefreq></url>
@endforeach
@foreach ($profiles as $profile)
    <url><loc>{{ route('profile.show', $profile->handle) }}</loc><lastmod>{{ $profile->updated_at?->toAtomString() }}</lastmod></url>
@endforeach
@foreach ($cvs as $cv)
    <url><loc>{{ route('cv.show', $cv->slug) }}</loc><lastmod>{{ $cv->updated_at?->toAtomString() }}</lastmod></url>
@endforeach
</urlset>

User-agent: *
Allow: /
@foreach ($disallowed as $path)
Disallow: /{{ $path }}
@endforeach

Sitemap: {{ $sitemap }}

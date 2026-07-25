<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ($urls as $url)
        <url>
            <loc>{{ $url->url }}</loc>
            @if ($url->lastModified)
                <lastmod>{{ $url->lastModified->toAtomString() }}</lastmod>
            @endif

            @if ($url->changeFrequency)
                <changefreq>{{ $url->changeFrequency }}</changefreq>
            @endif

            @if (! is_null($url->priority))
                <priority>{{ number_format($url->priority, 1) }}</priority>
            @endif
        </url>
    @endforeach
</urlset>

<!DOCTYPE html>
<html>
<head>
    {!! $head !!}
    {{-- Noto Sans TC for the card; PublicSitePolicy allows Google Fonts for
    `?ogimage` requests only. `block` keeps the screenshot from catching a
    fallback-font flash. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&display=block"
    />
    {{-- Published to (1) add the CSP nonce, without which the site's nonce-only
    style-src drops this rule and the card renders at its content height, and
    (2) drop the package's `*` reset: it is unlayered, so it would beat every
    Tailwind margin/padding utility, and preflight already resets them. --}}
    <style @cspNonce>
        html {
            font-family: 'Noto Sans TC', system-ui, sans-serif;
        }
        html,
        body {
            width: {{ $width }}px;
            height: {{ $height }}px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    {!! $templateContent !!}
</body>
</html>

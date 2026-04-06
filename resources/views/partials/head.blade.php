<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

@php
    $faviconPath = $branding?->favicon ?? 'assets/images/favicon-uniguajira-32x32.webp';
    $appleTouchPath = $branding?->logo_navbar ?? 'assets/images/logo-uniguajira-seo-150x150.webp';
@endphp
<link rel="icon" href="{{ asset($faviconPath) }}" type="image/webp">
<link rel="apple-touch-icon" href="{{ asset($appleTouchPath) }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

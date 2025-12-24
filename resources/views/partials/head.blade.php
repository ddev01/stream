<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

{{-- <link rel="icon" href="/favicon.ico" sizes="any"> --}}
<link type="image/webp" href="{{ asset('assets/icons/cat.webp') }}" rel="icon">
<link type="image/webp" href="{{ asset('assets/icons/cat.webp') }}" rel="apple-touch-icon">

<link href="https://fonts.bunny.net" rel="preconnect">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
@filamentStyles(['filament/support', 'filament/tables', 'filament/filament'])

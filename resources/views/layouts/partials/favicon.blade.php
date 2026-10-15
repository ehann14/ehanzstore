{{-- Favicon global EHANZSTORE (muncul di semua tab) --}}
@php
    $faviconPath = public_path('images/logo.png');
    $faviconVer  = file_exists($faviconPath) ? filemtime($faviconPath) : '1';
@endphp
<link rel="icon" type="image/png" sizes="any" href="{{ asset('images/logo.png') }}?v={{ $faviconVer }}">
<link rel="shortcut icon" type="image/png" href="{{ asset('images/logo.png') }}?v={{ $faviconVer }}">
<link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}?v={{ $faviconVer }}">
<meta name="theme-color" content="#020617">
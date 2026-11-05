@php
   $pwaApplicationName = trim((string) ($systemName ?? 'SpeakReady AI')) ?: 'SpeakReady AI';
   $pwaStatusBarStyle = $pwaStatusBarStyle ?? 'default';
@endphp
<meta name="application-name" content="{{ $pwaApplicationName }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ $pwaApplicationName }}">
<meta name="apple-mobile-web-app-status-bar-style" content="{{ $pwaStatusBarStyle }}">
<meta name="format-detection" content="telephone=no">
<link rel="manifest" href="{{ asset('manifest.json?v=11') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('img/apple-touch-icon-v2.png') }}?v=3">
<link rel="stylesheet" href="{{ asset('css/shared/pwa-install.css?v=1') }}">
<script src="{{ asset('js/pwa-install.js?v=2') }}" data-sr-pwa-controller></script>

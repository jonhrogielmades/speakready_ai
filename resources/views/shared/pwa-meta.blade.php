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
<link rel="manifest" href="{{ asset('manifest.json?v=13') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('img/apple-touch-icon.png') }}?v=4">
@include('shared.pwa-startup-images')
<link rel="stylesheet" href="{{ asset('css/shared/pwa-launch.css?v=1') }}">
<script>
    (function () {
        var standalone = (window.matchMedia && (
            window.matchMedia('(display-mode: standalone)').matches ||
            window.matchMedia('(display-mode: fullscreen)').matches ||
            window.matchMedia('(display-mode: minimal-ui)').matches
        )) || navigator.standalone === true;
        if (!standalone) return;
        if (window.SpeakReadyLayoutShellGuard && window.SpeakReadyLayoutShellGuard.redirecting) return;

        try {
            if (sessionStorage.getItem('sr-pwa-launch-shown') === '1') return;
            sessionStorage.setItem('sr-pwa-launch-shown', '1');
        } catch (_) {
            // Private browsing can disable session storage; the launch still works.
        }

        document.documentElement.classList.add('sr-pwa-launching');
        var themeMeta = document.querySelector('meta[name="theme-color"]');
        var previousThemeColor = themeMeta && themeMeta.getAttribute('content');
        if (themeMeta) themeMeta.setAttribute('content', '#071a33');
        var startedAt = Date.now();
        var finishing = false;
        function finishLaunch() {
            if (finishing) return;
            finishing = true;
            window.setTimeout(function () {
                document.documentElement.classList.remove('sr-pwa-launching');
                if (themeMeta && previousThemeColor) themeMeta.setAttribute('content', previousThemeColor);
            }, Math.max(0, 650 - (Date.now() - startedAt)));
        }
        window.addEventListener('DOMContentLoaded', finishLaunch, { once: true });
        window.setTimeout(finishLaunch, 4500);
    })();
</script>
<link rel="stylesheet" href="{{ asset('css/shared/pwa-install.css?v=1') }}">
<script src="{{ asset('js/pwa-install.js?v=2') }}" data-sr-pwa-controller></script>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Private Interview Review') · SpeakReady AI</title>
    <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/all.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/mobile/shared/public-review.css?v=2') }}" data-page-style="public-review-mobile">
</head>
<body>
    <header class="public-review-header">
        <div class="container py-3 d-flex align-items-center gap-2">
            <img src="{{ asset('img/logo.png') }}" width="34" height="34" alt="">
            <strong>SpeakReady AI</strong><span style="color:var(--tx3);font-size:.82rem;">Private review</span>
        </div>
    </header>
    <main class="public-review-shell">@yield('content')</main>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    @include('mobile.partials.flash-modal')
    @include('mobile.partials.page-transition')
</body>
</html>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Private Interview Review') · SpeakReady AI</title>
    <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/all.min.css') }}" rel="stylesheet">
    <style>
        :root { --bg:#f8fafc;--bg3:#eef2ff;--sf:#fff;--bd:#dbe3ef;--tx:#0f172a;--tx2:#334155;--tx3:#64748b; }
        @media (prefers-color-scheme: dark) { :root { --bg:#07111f;--bg3:#0f172a;--sf:#111c2e;--bd:#26354b;--tx:#f8fafc;--tx2:#cbd5e1;--tx3:#94a3b8; } }
        body { margin:0;background:var(--bg);color:var(--tx);font-family:system-ui,-apple-system,"Segoe UI",sans-serif; }
        .public-review-header { background:var(--sf);border-bottom:1px solid var(--bd); }
        .public-review-shell { width:min(1180px,calc(100% - 28px));margin:0 auto;padding:28px 0 56px; }
        .db-section { display:block; }
        .accordion-button:not(.collapsed) { background:rgba(59,130,246,.08);color:var(--tx); }
        .accordion-button::after { filter:none; }
        @media (prefers-color-scheme: dark) { .accordion-button::after { filter:invert(1); } }
    </style>
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
</body>
</html>

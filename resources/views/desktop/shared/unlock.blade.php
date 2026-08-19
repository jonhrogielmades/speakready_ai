<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Unlock Private Review · SpeakReady AI</title>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/desktop/shared/unlock.css?v=1') }}" data-page-style="unlock-desktop">
</head>
<body class="unlock-shell">
<main class="unlock-card">
    <div class="text-primary fw-bold mb-2">SpeakReady AI</div>
    <h1 class="h3">Private mentor review</h1>
    <p class="text-secondary">This time-limited review is password protected. Ask the owner for the password.</p>
    <form method="POST" action="{{ route('shared.unlock', $sessionRecord->share_token) }}">@csrf
        <label class="form-label">Review password</label>
        <input class="form-control mb-2" type="password" name="password" required autofocus>
        @error('password')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
        <button class="btn btn-primary w-100 mt-2">Unlock review</button>
    </form>
    <div class="text-secondary small mt-3">Expires {{ optional($sessionRecord->share_expires_at)->diffForHumans() }}.</div>
</main>
</body>
</html>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Unlock Private Review · SpeakReady AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light d-flex align-items-center" style="min-height:100vh">
<main class="container" style="max-width:480px">
    <div class="p-4 p-md-5 rounded-4 border border-secondary shadow" style="background:#111827">
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
    </div>
</main>
</body></html>

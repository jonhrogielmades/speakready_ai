@include('errors.page', [
    'code' => 429,
    'eyebrow' => 'You are moving fast',
    'title' => 'Give us a moment to catch up.',
    'message' => 'There have been several requests in a short time. Waiting briefly protects your account and keeps the experience reliable.',
    'icon' => 'refresh',
    'tone' => 'teal',
    'primaryLabel' => 'Try again',
    'secondaryMode' => 'reload',
    'secondaryLabel' => 'Refresh later',
])

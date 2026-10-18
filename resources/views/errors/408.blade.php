@include('errors.page', [
    'code' => 408,
    'eyebrow' => 'The connection took too long',
    'title' => 'Your request timed out.',
    'message' => 'The connection was taking longer than expected. A quick retry usually gets things moving again.',
    'icon' => 'refresh',
    'tone' => 'blue',
    'primaryLabel' => 'Try again',
    'secondaryMode' => 'reload',
    'secondaryLabel' => 'Refresh page',
])

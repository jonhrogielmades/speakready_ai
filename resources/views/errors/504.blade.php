@include('errors.page', [
    'code' => 504,
    'eyebrow' => 'The service took too long to respond',
    'title' => 'The connection needs another moment.',
    'message' => 'The request reached the service, but the response did not return in time.',
    'icon' => 'refresh',
    'tone' => 'coral',
    'primaryLabel' => 'Try again',
    'secondaryMode' => 'reload',
    'secondaryLabel' => 'Refresh page',
])

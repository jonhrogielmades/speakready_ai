@include('errors.page', [
    'code' => 502,
    'eyebrow' => 'The service is reconnecting',
    'title' => 'Our upstream service did not answer cleanly.',
    'message' => 'This is usually temporary. Please retry in a moment and we will get you back to practice.',
    'icon' => 'refresh',
    'tone' => 'coral',
    'primaryLabel' => 'Try again',
    'secondaryMode' => 'reload',
    'secondaryLabel' => 'Refresh page',
])

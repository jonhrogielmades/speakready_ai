@include('errors.page', [
    'code' => 419,
    'eyebrow' => 'Your session needs a refresh',
    'title' => 'You are almost there.',
    'message' => 'The page has been open for a while, so the security session expired. Refreshing will keep your next action safe.',
    'icon' => 'refresh',
    'tone' => 'violet',
    'primaryLabel' => 'Refresh securely',
    'secondaryMode' => 'reload',
    'secondaryLabel' => 'Reload page',
])

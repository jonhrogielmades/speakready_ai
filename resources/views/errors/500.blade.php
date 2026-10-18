@include('errors.page', [
    'code' => 500,
    'eyebrow' => 'A temporary server issue',
    'title' => 'Your progress deserves a careful retry.',
    'message' => 'Something unexpected happened on our side. Your saved practice data is not being replaced by this screen.',
    'icon' => 'refresh',
    'tone' => 'coral',
    'primaryLabel' => 'Try again',
    'secondaryMode' => 'reload',
    'secondaryLabel' => 'Refresh page',
    'hint' => 'If this keeps happening, please try again shortly or contact support with the time it occurred.',
])

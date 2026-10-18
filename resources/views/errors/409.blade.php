@include('errors.page', [
    'code' => 409,
    'eyebrow' => 'The request is already in progress',
    'title' => 'Let us keep your progress safe.',
    'message' => 'This action conflicts with a newer update. Nothing needs to be repeated until the current action finishes.',
    'icon' => 'refresh',
    'tone' => 'cyan',
    'hint' => 'Wait a moment, then refresh the page to see the latest state.',
    'secondaryMode' => 'reload',
    'secondaryLabel' => 'Refresh page',
])

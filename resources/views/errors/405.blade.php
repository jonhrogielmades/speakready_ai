@include('errors.page', [
    'code' => 405,
    'eyebrow' => 'Action not supported',
    'title' => 'This action is not available here.',
    'message' => 'The page is available, but it does not accept this kind of request.',
    'icon' => 'alert',
    'tone' => 'amber',
])

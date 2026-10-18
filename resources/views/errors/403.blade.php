@include('errors.page', [
    'code' => 403,
    'eyebrow' => 'Access is restricted',
    'title' => 'This space is not available to your account.',
    'message' => 'You may need a different permission or account to open this page.',
    'icon' => 'lock',
    'tone' => 'amber',
    'hint' => 'If you think this is a mistake, return to your dashboard or contact your administrator.',
])

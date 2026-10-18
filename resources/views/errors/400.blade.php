@include('errors.page', [
    'code' => 400,
    'eyebrow' => 'Request needs a quick check',
    'title' => 'That request could not be understood.',
    'message' => 'Something in the request was incomplete or formatted differently than expected.',
    'icon' => 'alert',
    'tone' => 'amber',
    'hint' => 'Please review the page and try again with the latest information.',
])

@include('errors.page', [
    'code' => 415,
    'eyebrow' => 'File type not supported',
    'title' => 'That format is not ready for this action.',
    'message' => 'Please choose a supported file type and try again.',
    'icon' => 'alert',
    'tone' => 'amber',
])

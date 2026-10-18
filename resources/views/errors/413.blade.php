@include('errors.page', [
    'code' => 413,
    'eyebrow' => 'That file is too large',
    'title' => 'Let us try a lighter upload.',
    'message' => 'The selected file is larger than this workspace can safely process.',
    'icon' => 'alert',
    'tone' => 'amber',
    'hint' => 'Choose a smaller file or compress it before uploading again.',
])

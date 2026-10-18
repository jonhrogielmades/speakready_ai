@include('errors.page', [
    'code' => 422,
    'eyebrow' => 'A few details need attention',
    'title' => 'We could not save that yet.',
    'message' => 'One or more values need to be corrected before we can continue.',
    'icon' => 'alert',
    'tone' => 'amber',
    'hint' => 'Return to the form, review the highlighted fields, and try again.',
])

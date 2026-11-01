@props(['name', 'size' => 24])
<svg
   {{ $attributes->merge(['class' => 'landing-icon']) }}
   width="{{ $size }}"
   height="{{ $size }}"
   viewBox="0 0 24 24"
   fill="none"
   stroke="currentColor"
   stroke-width="1.8"
   stroke-linecap="round"
   stroke-linejoin="round"
   aria-hidden="true"
   focusable="false"
>
   @switch($name)
      @case('arrow-right')
         <path d="M5 12h14M13 6l6 6-6 6" />
         @break
      @case('arrow-up')
         <path d="m6 11 6-6 6 6M12 5v14" />
         @break
      @case('sparkles')
         <path d="m12 3-1.2 3.2L7.5 7.5l3.3 1.3L12 12l1.2-3.2 3.3-1.3-3.3-1.3L12 3Z" />
         <path d="m5.5 13-.8 2.1-2.2.9 2.2.9.8 2.1.8-2.1 2.2-.9-2.2-.9-.8-2.1ZM18.5 14l-.8 2.1-2.2.9 2.2.9.8 2.1.8-2.1 2.2-.9-2.2-.9-.8-2.1Z" />
         @break
      @case('check')
         <path d="m5 12.5 4 4L19 7" />
         @break
      @case('target')
         <circle cx="12" cy="12" r="8" /><circle cx="12" cy="12" r="3" /><path d="M15 9 21 3M17 3h4v4" />
         @break
      @case('microphone')
         <rect x="9" y="3" width="6" height="11" rx="3" /><path d="M5.5 11.5a6.5 6.5 0 0 0 13 0M12 18v3M9 21h6" />
         @break
      @case('feedback')
         <path d="M20 15a3 3 0 0 1-3 3H9l-5 3v-3a3 3 0 0 1-1-2.2V7a3 3 0 0 1 3-3h11a3 3 0 0 1 3 3v8Z" /><path d="m8 11 2 2 5-5" />
         @break
      @case('briefcase')
         <rect x="3" y="7" width="18" height="12" rx="2" /><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18M10 12v2h4v-2" />
         @break
      @case('message')
         <path d="M21 12a8 8 0 0 1-8 8H6l-3 2v-5.5A8.5 8.5 0 1 1 21 12Z" /><path d="M8 12h.01M12 12h.01M16 12h.01" />
         @break
      @case('chart')
         <path d="M4 19V5M4 19h16" /><path d="m7 15 4-4 3 2 5-6" />
         @break
      @case('shield')
         <path d="M12 3 4.5 6v5.5c0 4.6 3.2 7.7 7.5 9.5 4.3-1.8 7.5-4.9 7.5-9.5V6L12 3Z" /><path d="m8.5 12 2.2 2.2 4.8-5" />
         @break
      @case('camera')
         <path d="M14.5 6 16 8h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3l1.5-2h5Z" /><circle cx="12" cy="13" r="3" />
         @break
      @case('sliders')
         <path d="M4 7h10M18 7h2M4 17h2M10 17h10" /><circle cx="16" cy="7" r="2" /><circle cx="8" cy="17" r="2" />
         @break
      @case('menu')
         <path d="M4 7h16M4 12h16M4 17h16" />
         @break
      @case('close')
         <path d="m6 6 12 12M18 6 6 18" />
         @break
      @case('sun')
         <circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.42-1.41M17.66 6.34l1.41-1.41" />
         @break
      @case('moon')
         <path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z" />
         @break
      @case('mail')
         <rect x="3" y="5" width="18" height="14" rx="2" /><path d="m4 7 8 6 8-6" />
         @break
      @case('location')
         <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10" r="2.5" />
         @break
      @case('phone')
         <path d="M5 3h3l1.5 4-2 1.5a15 15 0 0 0 8 8l1.5-2L21 16v3a2 2 0 0 1-2 2C10.2 21 3 13.8 3 5a2 2 0 0 1 2-2Z" />
         @break
      @case('play')
         <circle cx="12" cy="12" r="9" /><path d="m10 8 6 4-6 4V8Z" />
         @break
      @case('document')
         <path d="M6 3h8l4 4v14H6V3Z" /><path d="M14 3v5h5M9 13h6M9 17h6" />
         @break
      @case('lock')
         <rect x="5" y="10" width="14" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3M12 15v2" />
         @break
   @endswitch
</svg>

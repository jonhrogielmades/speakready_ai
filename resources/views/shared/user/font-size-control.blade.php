@php
   $fontSizeControlPrefix = $fontSizeControlPrefix ?? 'user';
   $fontSizeMenuId = $fontSizeControlPrefix.'FontSizeMenu';
@endphp

<div class="sr-font-size-control" data-font-size-control>
   <button
      class="{{ $fontSizeControlButtonClass ?? 'boc d-flex align-items-center justify-content-center' }}"
      id="{{ $fontSizeControlPrefix }}FontSizeBtn"
      type="button"
      aria-label="Adjust font size"
      title="Adjust font size"
      aria-haspopup="dialog"
      aria-expanded="false"
      aria-controls="{{ $fontSizeMenuId }}"
      data-font-size-toggle
   >
      <i class="fa-solid fa-text-height" aria-hidden="true"></i>
   </button>
   <div
      class="sr-font-size-menu"
      id="{{ $fontSizeMenuId }}"
      role="dialog"
      aria-label="Font size adjustment"
      data-font-size-menu
      hidden
   >
      <div class="sr-font-size-menu-header">
         <span class="sr-font-size-menu-title"><i class="fa-solid fa-text-height" aria-hidden="true"></i>Font size</span>
         <output data-font-size-value aria-live="polite">100%</output>
      </div>
      <div class="sr-font-size-actions" role="group" aria-label="Font size controls">
         <button type="button" class="sr-font-size-action" data-font-size-action="decrease" aria-label="Decrease font size">
            <span class="sr-font-size-letter" aria-hidden="true">A<small>−</small></span>
         </button>
         <button type="button" class="sr-font-size-action" data-font-size-action="reset" aria-label="Reset font size to 100%">
            <span class="sr-font-size-letter" aria-hidden="true">A</span>
         </button>
         <button type="button" class="sr-font-size-action" data-font-size-action="increase" aria-label="Increase font size">
            <span class="sr-font-size-letter" aria-hidden="true">A<small>+</small></span>
         </button>
      </div>
      <span class="sr-font-size-help">Adjust the reading size for this app.</span>
   </div>
</div>

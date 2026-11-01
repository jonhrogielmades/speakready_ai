<header id="nbar" class="landing-header">
   <div class="landing-container landing-nav">
      <a class="landing-brand" href="#hero" aria-label="{{ $systemName ?? 'SpeakReady AI' }} home">
         <img src="{{ asset($systemLogo ?? 'img/logo.png') }}" alt="" width="48" height="48" decoding="async">
         <span class="landing-brand__text">{{ $systemName ?? 'SpeakReady AI' }}</span>
      </a>

      <nav class="landing-nav__links" aria-label="Primary navigation">
         <a href="#how">How it works</a>
         <a href="#features">Benefits</a>
         <a href="#sample-feedback">Sample feedback</a>
         <a href="#faq">FAQ</a>
      </nav>

      <div class="landing-nav__actions">
         <button type="button" class="landing-button landing-button--ghost landing-theme-toggle" id="thbtn" aria-label="Switch theme">
            <span class="landing-theme-toggle__icon" data-theme-icon="moon" aria-hidden="true">☾</span>
         </button>
         <a class="landing-button landing-button--ghost" href="{{ $landingLoginUrl }}">Log in</a>
         <a class="landing-button landing-button--primary" href="{{ $landingRegisterUrl }}">
            Start free
            <x-landing.icon name="arrow-right" size="18" />
         </a>
      </div>

      <button
         type="button"
         class="landing-nav__toggle"
         id="guestNavToggle"
         aria-expanded="false"
         aria-controls="guestNavPanel"
         aria-label="Open navigation"
      >
         <span class="landing-nav__toggle-menu" aria-hidden="true"><x-landing.icon name="menu" /></span>
         <span class="landing-nav__toggle-close" aria-hidden="true"><x-landing.icon name="close" /></span>
      </button>
   </div>

   <nav id="guestNavPanel" class="landing-nav__panel" aria-label="Mobile navigation" hidden>
      <a href="#how">How it works</a>
      <a href="#features">Benefits</a>
      <a href="#sample-feedback">Sample feedback</a>
      <a href="#faq">FAQ</a>
      <button type="button" class="landing-button landing-button--secondary landing-theme-toggle" id="thbtnMobile" aria-label="Switch theme">
         <span class="landing-theme-toggle__icon" data-theme-icon="moon" aria-hidden="true">☾</span>
         <span>Switch theme</span>
      </button>
      <a href="{{ $landingLoginUrl }}">Log in</a>
      <a class="landing-button landing-button--primary" href="{{ $landingRegisterUrl }}">Start practicing free</a>
   </nav>
</header>

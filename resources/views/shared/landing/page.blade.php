@php
   $landingVariant = ($landingVariant ?? 'desktop') === 'mobile' ? 'mobile' : 'desktop';
   $landingLayoutQuery = sr_layout_query(request());
   $landingLoginUrl = route('login.form', $landingLayoutQuery);
   $landingRegisterUrl = route('register.form', $landingLayoutQuery);
   $landingContactEmail = config('mail.contact_to') ?: 'support@speakreadyai.online';
   $landingContactPhone = $systemContactNumber ?? '';

   $landingStatDefinitions = [
      [
         'key' => 'registered_users',
         'label' => 'Registered learners',
         'suffix' => '',
      ],
      [
         'key' => 'interview_sessions',
         'label' => 'Practice sessions',
         'suffix' => '',
      ],
      [
         'key' => 'questions_available',
         'label' => 'Active questions',
         'suffix' => '',
      ],
      [
         'key' => 'feedback_generated',
         'label' => 'Feedback reviews',
         'suffix' => '',
      ],
      [
         'key' => 'success_rate',
         'label' => 'Readiness scores at 75+',
         'suffix' => '%',
      ],
   ];

   $landingVisibleStats = array_values(array_filter(
      $landingStatDefinitions,
      fn (array $stat): bool => (int) data_get($landingStats ?? [], $stat['key'].'.value', 0) > 0
   ));
@endphp

<a class="sr-skip-link" href="#main-content">Skip to main content</a>

<div id="landing">
   @include('shared.landing.header')

   <main id="main-content" tabindex="-1">
      @include('shared.landing.hero')
      @include('shared.landing.proof')
      @include('shared.landing.journey')
      @include('shared.landing.feedback')
      @include('shared.landing.trust-faq')
      @include('shared.landing.contact-cta')
   </main>

   @include('shared.landing.footer')
</div>

<dialog id="pwaInstallDialog" class="landing-dialog" aria-labelledby="pwaInstallTitle" aria-describedby="pwaInstallMessage">
   <div class="landing-dialog__body">
      <div class="landing-dialog__header">
         <div class="landing-dialog__icon" aria-hidden="true">
            <x-landing.icon name="sparkles" />
         </div>
         <button type="button" id="pwaInstallClose" class="landing-dialog__close" aria-label="Close install information">
            <x-landing.icon name="close" />
         </button>
      </div>
      <h2 id="pwaInstallTitle">Keep SpeakReady AI within reach</h2>
      <p id="pwaInstallMessage">Install SpeakReady AI for quick access from your home screen or app menu.</p>
      <div class="landing-dialog__actions">
         <button type="button" id="pwaInstallCancel" class="landing-button landing-button--secondary">Not now</button>
         <button type="button" id="pwaInstallConfirm" class="landing-button landing-button--primary">Install app</button>
      </div>
   </div>
</dialog>

<button type="button" id="backToTopBtn" class="back-to-top" aria-label="Back to top" hidden>
   <x-landing.icon name="arrow-up" />
</button>

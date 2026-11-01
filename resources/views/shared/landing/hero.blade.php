<section id="hero" class="landing-hero" aria-labelledby="heroTitle">
   <div class="landing-container landing-hero__grid">
      <div class="landing-hero__copy reveal is-visible">
         <div class="landing-hero__eyebrow">AI interview practice for job seekers</div>
         <h1 id="heroTitle" class="landing-hero__title">
            Practice your next interview. <span>Know what to improve.</span>
         </h1>
         <p class="landing-hero__lede">
            Answer role-focused questions by voice or text, then review clear, evidence-based feedback on what worked and what to strengthen.
         </p>
         <div class="landing-hero__actions">
            <a class="landing-button landing-button--primary" href="{{ $landingRegisterUrl }}">
               Start practicing free
               <x-landing.icon name="arrow-right" size="19" />
            </a>
            <a class="landing-button landing-button--secondary" href="#sample-feedback">
               <x-landing.icon name="play" size="19" />
               See sample feedback
            </a>
            <button type="button" class="landing-button landing-button--ghost" id="heroInstallBtn" hidden>
               Install app
            </button>
         </div>
         <ul class="landing-hero__trust" aria-label="Practice options">
            <li>Free to start</li>
            <li>Camera optional</li>
            <li>Text, voice, or hybrid</li>
         </ul>
      </div>

      <div class="landing-hero__visual reveal is-visible">
         <div class="product-stage">
            <figure class="product-window">
               <div class="product-window__bar">
                  <span aria-hidden="true"></span>
                  <span class="visually-hidden">SpeakReady AI dashboard preview</span>
               </div>
               <picture>
                  <source
                     media="(max-width: 767px)"
                     srcset="{{ asset('img/mobile-preview/01-home-dashboard-hd.webp') }}?v=20261020"
                     width="1260"
                     height="2520"
                     type="image/webp"
                  >
                  <source
                     media="(min-width: 768px)"
                     srcset="{{ asset('img/desktop-preview/01_Overview_Desktop_Preview_4K.webp') }}?v=20261020"
                     width="3840"
                     height="2516"
                     type="image/webp"
                  >
                  <img
                     class="product-window__image"
                     src="{{ asset('img/desktop-preview/01_Overview_Desktop_Preview_4K.webp') }}?v=20261020"
                     alt="SpeakReady AI dashboard with readiness, feedback, progress, and recent practice sessions"
                     width="3840"
                     height="2516"
                     loading="eager"
                     fetchpriority="high"
                     decoding="async"
                  >
               </picture>
            </figure>

            <div class="product-float product-float--top" aria-hidden="true">
               <span class="product-float__icon"><x-landing.icon name="briefcase" size="18" /></span>
               <span><strong>Role-ready practice</strong><span>Questions shaped around your target role</span></span>
            </div>
            <div class="product-float product-float--bottom" aria-hidden="true">
               <span class="product-float__icon"><x-landing.icon name="feedback" size="18" /></span>
               <span><strong>Actionable feedback</strong><span>Clear strengths and focused next steps</span></span>
            </div>
            <div class="product-float product-float--middle" aria-hidden="true">
               <span class="product-float__icon"><x-landing.icon name="chart" size="18" /></span>
               <span><strong>Progress you can follow</strong><span>Sessions, trends, reports, and goals</span></span>
            </div>
         </div>
      </div>
   </div>
</section>

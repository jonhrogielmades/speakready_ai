<footer class="landing-footer">
   <div class="landing-container">
      <div class="landing-footer__grid">
         <div class="landing-footer__brand">
            <a class="landing-brand" href="#hero" aria-label="{{ $systemName ?? 'SpeakReady AI' }} home">
               <img src="{{ asset($systemLogo ?? 'img/logo.png') }}" alt="" width="48" height="48" loading="lazy" decoding="async">
               <span class="landing-brand__text">{{ $systemName ?? 'SpeakReady AI' }}</span>
            </a>
            <p>{{ $systemDescription ?? 'Role-focused interview practice with flexible response modes, actionable feedback, and progress you can revisit.' }}</p>
            <p class="landing-footer__team">Project team: Jonh Rogiel M. Tumanda, Karyl G. Gesto, and Eva Mae C. Cabilic.</p>
         </div>

         <nav class="landing-footer__links" aria-label="Footer navigation">
            <div>
               <h2>Explore</h2>
               <ul>
                  <li><a href="#how">How it works</a></li>
                  <li><a href="#features">Benefits</a></li>
                  <li><a href="#sample-feedback">Sample feedback</a></li>
                  <li><a href="#faq">FAQ</a></li>
               </ul>
            </div>
            <div>
               <h2>Account</h2>
               <ul>
                  <li><a href="{{ $landingLoginUrl }}">Log in</a></li>
                  <li><a href="{{ $landingRegisterUrl }}">Create an account</a></li>
                  <li><a href="#contact">Contact</a></li>
               </ul>
            </div>
         </nav>

         <div class="landing-footer__newsletter">
            <h2>Practice updates</h2>
            <p>Receive occasional product updates and interview-practice tips.</p>
            @if(session('newsletter_success'))
               <div class="form-alert form-alert--success" role="status">{{ session('newsletter_success') }}</div>
            @endif
            @if($errors->newsletter->any())
               <div class="form-alert form-alert--error" role="alert">{{ $errors->newsletter->first() }}</div>
            @endif
            <form class="landing-newsletter" action="{{ route('newsletter.subscribe') }}" method="POST">
               @csrf
               <label class="visually-hidden" for="footerNewsletterEmail">Email address</label>
               <input
                  id="footerNewsletterEmail"
                  name="email"
                  type="email"
                  value="{{ $errors->newsletter->any() ? old('email') : '' }}"
                  placeholder="Email address"
                  autocomplete="email"
                  inputmode="email"
                  required
               >
               <button type="submit" class="landing-button landing-button--primary" aria-label="Subscribe to practice updates">
                  <x-landing.icon name="arrow-right" size="18" />
               </button>
            </form>
         </div>
      </div>

      <div class="landing-footer__bottom">
         <p>{{ html_entity_decode($systemFooter ?? '&copy; '.date('Y').' SpeakReady AI. All rights reserved.') }}</p>
         <nav class="landing-footer__legal" aria-label="Legal">
            <a href="{{ route('legal.privacy') }}">Privacy</a>
            <a href="{{ route('legal.terms') }}">Terms</a>
            <a href="{{ route('legal.security') }}">Security</a>
            <a href="{{ route('legal.cookies') }}">Cookies</a>
         </nav>
      </div>
   </div>
</footer>

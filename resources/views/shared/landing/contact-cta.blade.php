<section class="landing-section" aria-labelledby="finalCtaTitle">
   <div class="landing-container">
      <div class="final-cta reveal">
         <h2 id="finalCtaTitle">Walk into your next interview with a clearer plan</h2>
         <p>Start with one role-focused practice session, review what worked, and use the next step to make your answer stronger.</p>
         <div class="final-cta__actions">
            <a class="landing-button landing-button--primary" href="{{ $landingRegisterUrl }}">
               Start practicing free
               <x-landing.icon name="arrow-right" size="19" />
            </a>
            <a class="landing-button landing-button--secondary" href="{{ $landingLoginUrl }}">I already have an account</a>
         </div>
      </div>
   </div>
</section>

<section id="contact" class="landing-section landing-section--muted" aria-labelledby="contactTitle">
   <div class="landing-container">
      <div class="section-heading reveal">
         <span class="section-heading__eyebrow">Questions?</span>
         <h2 id="contactTitle">Talk to the SpeakReady AI team</h2>
         <p>Need help choosing a practice mode or understanding how the platform works? Send a message and include enough detail for the team to help.</p>
      </div>

      <div class="contact-grid">
         <aside class="contact-card reveal" aria-label="Contact information">
            <h3>We’re here to help</h3>
            <p>Use the form for product questions, access concerns, or feedback about your experience.</p>
            <div class="contact-methods">
               <a href="mailto:{{ $landingContactEmail }}" class="contact-method">
                  <span class="contact-method__icon"><x-landing.icon name="mail" /></span>
                  <span><strong>Email</strong><span>{{ $landingContactEmail }}</span></span>
               </a>
               @if(filled($landingContactPhone))
                  <a href="tel:{{ preg_replace('/[^0-9+]/', '', $landingContactPhone) }}" class="contact-method">
                     <span class="contact-method__icon"><x-landing.icon name="phone" /></span>
                     <span><strong>Phone</strong><span>{{ $landingContactPhone }}</span></span>
                  </a>
               @endif
               <div class="contact-method">
                  <span class="contact-method__icon"><x-landing.icon name="location" /></span>
                  <span><strong>Location</strong><span>SLSU Bontoc Campus, Southern Leyte</span></span>
               </div>
            </div>
         </aside>

         <div class="contact-card reveal">
            @if(session('contact_success'))
               <div class="form-alert form-alert--success" role="status">
                  <x-landing.icon name="check" size="20" />
                  <span>{{ session('contact_success') }}</span>
               </div>
            @endif

            @if($errors->contact->any())
               <div class="form-alert form-alert--error" role="alert" tabindex="-1">
                  <x-landing.icon name="feedback" size="20" />
                  <div>
                     <strong>Please check the following:</strong>
                     <ul>
                        @foreach($errors->contact->all() as $contactError)
                           <li>{{ $contactError }}</li>
                        @endforeach
                     </ul>
                  </div>
               </div>
            @endif

            @if(session('contact_error'))
               <div class="form-alert form-alert--error" role="alert">
                  <x-landing.icon name="feedback" size="20" />
                  <span>{{ session('contact_error') }}</span>
               </div>
            @endif

            <form class="landing-form" action="{{ route('contact.send') }}" method="POST">
               @csrf
               <div class="field">
                  <label for="contactName">Name</label>
                  <input
                     id="contactName"
                     name="name"
                     type="text"
                     value="{{ $errors->contact->any() ? old('name') : '' }}"
                     placeholder="Your full name"
                     autocomplete="name"
                     @error('name', 'contact') aria-invalid="true" aria-describedby="contactNameError" @enderror
                     required
                  >
                  @error('name', 'contact')<span id="contactNameError" class="field-error">{{ $message }}</span>@enderror
               </div>

               <div class="field">
                  <label for="contactEmail">Email</label>
                  <input
                     id="contactEmail"
                     name="email"
                     type="email"
                     value="{{ $errors->contact->any() ? old('email') : '' }}"
                     placeholder="you@example.com"
                     autocomplete="email"
                     inputmode="email"
                     @error('email', 'contact') aria-invalid="true" aria-describedby="contactEmailError" @enderror
                     required
                  >
                  @error('email', 'contact')<span id="contactEmailError" class="field-error">{{ $message }}</span>@enderror
               </div>

               <div class="field">
                  <label for="contactSubject">Subject</label>
                  <input
                     id="contactSubject"
                     name="subject"
                     type="text"
                     value="{{ $errors->contact->any() ? old('subject') : '' }}"
                     placeholder="How can we help?"
                     @error('subject', 'contact') aria-invalid="true" aria-describedby="contactSubjectError" @enderror
                     required
                  >
                  @error('subject', 'contact')<span id="contactSubjectError" class="field-error">{{ $message }}</span>@enderror
               </div>

               <div class="field">
                  <label for="contactMessage">Message</label>
                  <textarea
                     id="contactMessage"
                     name="message"
                     placeholder="Tell us what you need help with"
                     @error('message', 'contact') aria-invalid="true" aria-describedby="contactMessageError" @enderror
                     required
                  >{{ $errors->contact->any() ? old('message') : '' }}</textarea>
                  @error('message', 'contact')<span id="contactMessageError" class="field-error">{{ $message }}</span>@enderror
               </div>

               <button type="submit" class="landing-button landing-button--primary">
                  Send message
                  <x-landing.icon name="arrow-right" size="18" />
               </button>
            </form>
         </div>
      </div>
   </div>
</section>

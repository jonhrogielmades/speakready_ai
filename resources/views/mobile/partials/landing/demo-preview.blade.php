         <!-- MOBILE HERO PREVIEW -->
         <section id="demo-preview" class="sp position-relative mobile-static-demo-preview">
            <div class="aur aur-a" style="top:50%;left:50%;transform:translate(-50%,-50%)"></div>
            <div class="container position-relative" style="z-index:1">
               @php
                  $mobileHeroImage = asset('img/mobile-preview/01-home-dashboard-hd.png').'?v=20261012';
               @endphp
               <div class="mobile-hero-preview-row rv">
                  <figure class="mobile-hero-image-frame mb-0" aria-label="SpeakReady AI mobile dashboard hero image">
                     <img
                        class="mobile-hero-image"
                        src="{{ $mobileHeroImage }}"
                        alt="SpeakReady AI mobile dashboard overview"
                        width="1260"
                        height="2520"
                        loading="lazy"
                        decoding="async"
                     >
                     <div class="mobile-hero-context-card mobile-hero-context-card--top-left">
                        <span class="mobile-hero-context-kicker"><i class="fa-solid fa-briefcase mobile-hero-context-kicker-icon" aria-hidden="true"></i> Practice</span>
                        <strong>Role-ready drills</strong>
                        <span>Job focus / Timing / Tone</span>
                     </div>
                     <div class="mobile-hero-context-card mobile-hero-context-card--middle-left">
                        <span class="mobile-hero-context-kicker"><i class="fa-solid fa-chart-line mobile-hero-context-kicker-icon" aria-hidden="true"></i> Readiness</span>
                        <strong>Live score pulse</strong>
                        <span>Boost target / Trend</span>
                     </div>
                     <div class="mobile-hero-context-card mobile-hero-context-card--bottom-left">
                        <span class="mobile-hero-context-kicker"><i class="fa-solid fa-clipboard-check mobile-hero-context-kicker-icon" aria-hidden="true"></i> Feedback</span>
                        <strong>Actionable notes</strong>
                        <span>Strengths / Next steps</span>
                     </div>
                     <div class="mobile-hero-context-card mobile-hero-context-card--top-right">
                        <span class="mobile-hero-context-kicker"><i class="fa-solid fa-shield-alt mobile-hero-context-kicker-icon" aria-hidden="true"></i> Secure</span>
                        <strong>Private practice</strong>
                        <span>Camera / Mic controls</span>
                     </div>
                     <div class="mobile-hero-context-card mobile-hero-context-card--middle-right">
                        <span class="mobile-hero-context-kicker"><i class="fa-solid fa-robot mobile-hero-context-kicker-icon" aria-hidden="true"></i> Coaching</span>
                        <strong>AI session guide</strong>
                        <span>Hints / Follow-up / Clarity</span>
                     </div>
                     <div class="mobile-hero-context-card mobile-hero-context-card--bottom-right">
                        <span class="mobile-hero-context-kicker"><i class="fa-solid fa-bolt mobile-hero-context-kicker-icon" aria-hidden="true"></i> Progress</span>
                        <strong>Streak momentum</strong>
                        <span>Sessions / XP / Goals</span>
                     </div>
                  </figure>
               </div>
            </div>
         </section>

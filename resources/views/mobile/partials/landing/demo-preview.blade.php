         <!-- MOBILE HERO PREVIEW -->
         <section id="demo-preview" class="sp position-relative mobile-static-demo-preview">
            <div class="aur aur-a" style="top:50%;left:50%;transform:translate(-50%,-50%)"></div>
            <div class="container position-relative" style="z-index:1">
               @php
                  $heroVideo = asset('videos/speakready-ai-15s-product-ad.mp4').'?v=20261102';
               @endphp
               <div class="mobile-hero-preview-row rv">
                  <figure class="mobile-hero-image-frame mb-0" aria-label="SpeakReady AI mobile product overview video">
                     <div class="hero-video-showcase">
                        <div class="hero-video-kicker">
                           <span class="hero-video-kicker-title"><i class="fa-solid fa-play" aria-hidden="true"></i> See it in action</span>
                           <span class="hero-video-kicker-meta">15-sec tour</span>
                        </div>
                        <div class="hero-video-shell">
                           <div class="hero-video-chrome" aria-hidden="true">
                              <span class="hero-video-chrome-dots"><i></i><i></i><i></i></span>
                              <span class="hero-video-chrome-label">speakready.ai</span>
                           </div>
                           <video
                              class="mobile-hero-image mobile-hero-video"
                              width="1920"
                              height="1080"
                              autoplay
                              muted
                              loop
                              playsinline
                              preload="metadata"
                              aria-label="SpeakReady AI mobile product overview"
                           >
                              <source src="{{ $heroVideo }}" type="video/mp4">
                           </video>
                        </div>
                        <p class="hero-video-caption">Practice interviews. Get real-time coaching. Improve with every session.</p>
                        <div class="hero-benefit-row" role="list" aria-label="SpeakReady AI benefits">
                           <span class="hero-benefit-item" role="listitem"><i class="fa-solid fa-briefcase" aria-hidden="true"></i><span>Role-focused practice</span></span>
                           <span class="hero-benefit-item" role="listitem"><i class="fa-solid fa-comments" aria-hidden="true"></i><span>Real-time feedback</span></span>
                           <span class="hero-benefit-item" role="listitem"><i class="fa-solid fa-chart-line" aria-hidden="true"></i><span>Evidence-based coaching</span></span>
                        </div>
                     </div>
                  </figure>
               </div>
            </div>
         </section>

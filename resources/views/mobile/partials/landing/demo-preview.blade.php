         <!-- MOBILE HERO PREVIEW -->
         <section id="demo-preview" class="sp position-relative mobile-static-demo-preview">
            <div class="aur aur-a" style="top:50%;left:50%;transform:translate(-50%,-50%)"></div>
            <div class="container position-relative" style="z-index:1">
               @php
                  $heroVideo = asset('videos/speakready-ai-15s-product-ad.mp4').'?v=20261102';
               @endphp
               <div class="mobile-hero-preview-row rv">
                  <figure class="mobile-hero-image-frame mb-0" aria-label="SpeakReady AI mobile product overview video">
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
                  </figure>
               </div>
            </div>
         </section>

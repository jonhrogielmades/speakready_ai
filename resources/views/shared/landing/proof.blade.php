<section class="landing-proof" aria-labelledby="landingProofTitle">
   <div class="landing-container">
      <h2 id="landingProofTitle" class="visually-hidden">
         {{ count($landingVisibleStats) ? 'SpeakReady AI activity' : 'Flexible interview practice' }}
      </h2>
      <div class="landing-proof__grid reveal">
         @if(count($landingVisibleStats))
            @foreach($landingVisibleStats as $stat)
               @php
                  $statValue = (int) data_get($landingStats ?? [], $stat['key'].'.value', 0);
                  $statDisplay = data_get($landingStats ?? [], $stat['key'].'.display', number_format($statValue));
               @endphp
               <div class="landing-proof__item" data-landing-stat="{{ str_replace('_', '-', $stat['key']) }}">
                  <div class="pnum"><span class="counter" data-count="{{ $statValue }}">{{ $statDisplay }}</span>{{ $stat['suffix'] }}</div>
                  <div class="plbl">{{ $stat['label'] }}</div>
               </div>
            @endforeach
         @else
            <div class="landing-proof__item"><strong>Role focused</strong><span>Practice around your target position</span></div>
            <div class="landing-proof__item"><strong>Flexible</strong><span>Text, voice, or hybrid responses</span></div>
            <div class="landing-proof__item"><strong>Camera optional</strong><span>You control the practice setup</span></div>
            <div class="landing-proof__item"><strong>Actionable</strong><span>Evidence-based feedback and next steps</span></div>
         @endif
      </div>
   </div>
</section>

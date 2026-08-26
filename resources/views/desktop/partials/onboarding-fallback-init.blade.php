@php
    $tutorialPageTitle = trim($__env->yieldContent('page-title')) ?: (trim($__env->yieldContent('title')) ?: 'Overview');
    $tutorialRouteName = request()->route() ? request()->route()->getName() : null;
@endphp

<script>
    (function() {
        window.SpeakReadyTourContext = {
            routeName: @json($tutorialRouteName),
            pageTitle: @json($tutorialPageTitle),
            pageScope: @json(request()->getRequestUri()),
            autoStart: false,
            serverDetectedMobile: @json((bool) ($isMobile ?? false)),
        };

        window.setTimeout(function() {
            if (typeof window.initSpeakReadyFallbackTour === 'function') {
                window.initSpeakReadyFallbackTour(window.SpeakReadyTourContext);
            }
        }, 160);
    })();
</script>

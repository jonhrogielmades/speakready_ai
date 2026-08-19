<?php

if (! function_exists('mobile_view')) {
    /**
     * Resolve and return the correct desktop or mobile Blade view.
     *
     * This is the procedural equivalent of Controller::mobileView() for use
     * in route closures and other non-controller contexts.
     *
     * Resolution order:
     *   1. `desktop.<view>` when the request is desktop
     *   2. `mobile.<view>`  when the request is mobile
     *   3. Falls back to the original `<view>` if the prefixed variant doesn't exist.
     *
     * @param  string  $view   Dot-notation view name, e.g. 'interview.setup'
     * @param  array   $data   Data to pass to the view
     * @return \Illuminate\View\View
     */
    function mobile_view(string $view, array $data = []): \Illuminate\View\View
    {
        $isMobile = (bool) request()->attributes->get('is_mobile', false);
        $prefix   = $isMobile ? 'mobile' : 'desktop';
        $resolved = "{$prefix}.{$view}";

        if (! \Illuminate\Support\Facades\View::exists($resolved)) {
            $resolved = $view;
        }

        return view($resolved, array_merge($data, ['isMobile' => $isMobile]));
    }
}

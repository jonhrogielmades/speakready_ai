<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewResponse;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Return the correct desktop or mobile view automatically.
     *
     * Resolves to `desktop.<view>` or `mobile.<view>` based on whether the
     * current request was detected as mobile by the DetectMobile middleware.
     * Falls back to the original `<view>` path if the prefixed variant does
     * not exist yet, allowing safe incremental migration.
     *
     * Usage in any controller:
     *   return $this->mobileView('dashboard', compact('profile', 'data'));
     *   return $this->mobileView('user.account', compact('user'));
     *
     * @param  string  $view   Dot-notation view name, e.g. 'dashboard' or 'user.account'
     * @param  array   $data   Data to pass to the view
     * @return \Illuminate\View\View
     */
    protected function mobileView(string $view, array $data = []): ViewResponse
    {
        $isMobile = (bool) request()->attributes->get('is_mobile', false);
        $prefix   = $isMobile ? 'mobile' : 'desktop';
        $resolved = "{$prefix}.{$view}";

        // Graceful fallback: use original view if prefixed variant doesn't exist yet
        if (! View::exists($resolved)) {
            $resolved = $view;
        }

        return view($resolved, array_merge($data, ['isMobile' => $isMobile]));
    }
}

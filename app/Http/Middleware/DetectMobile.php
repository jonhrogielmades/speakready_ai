<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class DetectMobile
{
    /**
     * Mobile User-Agent patterns.
     */
    protected string $mobilePattern = '/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|webOS|Windows Phone|Silk\/|Kindle|Opera Mobi|Fennec|Minimo|Dolfin|Skyfire|wv|Macintosh.*Touch|SM-|Pixel|Nexus|FBAN|FBAV|Instagram|Line|Snapchat/i';

    private const MOBILE_VIEWPORT_MAX = 820;

    /**
     * Handle an incoming request.
     *
     * Detects whether the request comes from a mobile device via User-Agent,
     * then shares an $isMobile boolean with all Blade views.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isMobile = $this->detectMobile($request);

        $request->attributes->set('is_mobile', $isMobile);

        // Share with every Blade view
        View::share('isMobile', $isMobile);

        return $next($request);
    }

    private function detectMobile(Request $request): bool
    {
        $explicitLayout = function_exists('sr_explicit_request_layout_preference')
            ? sr_explicit_request_layout_preference($request)
            : null;
        $hasMobileDeviceSignal = $this->hasMobileDeviceSignal($request);

        if ($request->isMethod('GET') && in_array($explicitLayout, ['mobile', 'desktop'], true)) {
            if ($explicitLayout === 'desktop' && $hasMobileDeviceSignal) {
                if (function_exists('sr_remember_layout_preference')) {
                    sr_remember_layout_preference($request, 'mobile');
                }

                return true;
            }

            if (function_exists('sr_remember_layout_preference')) {
                sr_remember_layout_preference($request, $explicitLayout);
            }

            return $explicitLayout === 'mobile';
        }

        $isMobile = $hasMobileDeviceSignal;

        if ($request->cookie('sr_is_mobile') === '1') {
            $isMobile = true;
        }

        $viewportWidth = filter_var($request->cookie('sr_viewport_width'), FILTER_VALIDATE_INT);
        if ($viewportWidth !== false && $viewportWidth > 0 && $viewportWidth <= self::MOBILE_VIEWPORT_MAX) {
            $isMobile = true;
        }

        $sessionLayout = function_exists('sr_session_layout_preference')
            ? sr_session_layout_preference($request)
            : null;

        if ($sessionLayout === 'mobile') {
            $isMobile = true;
        }

        if (function_exists('sr_remember_layout_preference')) {
            sr_remember_layout_preference($request, $isMobile ? 'mobile' : 'desktop');
        }

        return $isMobile;
    }

    private function hasMobileDeviceSignal(Request $request): bool
    {
        $userAgent = $this->userAgent($request);
        if ($userAgent !== '' && (bool) preg_match($this->mobilePattern, $userAgent)) {
            return true;
        }

        $clientHint = $request->headers->get('Sec-CH-UA-Mobile', '');
        if (in_array($clientHint, ['?1', '1', 'true'], true)) {
            return true;
        }

        $queryIsMobile = strtolower((string) $request->query('sr_is_mobile', ''));
        if (in_array($queryIsMobile, ['1', 'true'], true)) {
            return true;
        }

        $queryViewportWidth = filter_var($request->query('sr_viewport_width'), FILTER_VALIDATE_INT);

        return $queryViewportWidth !== false
            && $queryViewportWidth > 0
            && $queryViewportWidth <= self::MOBILE_VIEWPORT_MAX;
    }

    private function userAgent(Request $request): string
    {
        return trim(implode(' ', array_filter([
            $request->headers->get('User-Agent'),
            $request->server('HTTP_USER_AGENT'),
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $request->headers->get('X-OperaMini-Phone-UA'),
            $request->headers->get('X-Device-User-Agent'),
        ])));
    }
}

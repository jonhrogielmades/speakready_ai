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

    /**
     * Handle an incoming request.
     *
     * Detects whether the request comes from a mobile device via User-Agent,
     * then shares an $isMobile boolean with all Blade views.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->header('User-Agent', '');
        $isMobile  = (bool) preg_match($this->mobilePattern, $userAgent);

        // Share with every Blade view
        View::share('isMobile', $isMobile);

        return $next($request);
    }
}
